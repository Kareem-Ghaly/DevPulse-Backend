<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Events\ProjectProposalChanged;
use App\Http\Resources\ProjectProposalResource;
use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\ProjectProposal;
use App\Models\ProjectTeam;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProjectProposalService
{
    use ApiResponse;

    public function __construct(
        private readonly ProjectProposalRepositoryInterface $repository,
        private readonly UserRepositoryInterface $users,
        private readonly NotificationService $notifications,
    ) {}

    public function index()
    {
        $proposals = $this->repository->all();

        return $this->successResponse(
            ProjectProposalResource::collection($proposals),
            'Project proposals retrieved successfully'
        );
    }

    public function store(array $data)
    {
        $teamId = $data['project_team_id'] ?? null;

        if ($teamId) {
            $team = ProjectTeam::find($teamId);
            if ($team && ! ($team->leader_id === auth()->id() || $team->members()->where('user_id', auth()->id())->exists())) {
                $team = null;
            }
        } else {
            $team = $this->getUserTeam();
        }

        if (! $team) {
            return $this->errorResponse('You must belong to a project team.', null, 422);
        }

        $existing = ProjectProposal::where('project_team_id', $team->id)->first();
        if ($existing) {
            return $this->errorResponse(
                'Team already has a proposal',
                ['proposal_id' => $existing->id],
                422
            );
        }

        $data['project_team_id'] = $team->id;
        $data['created_by'] = auth()->id();
        $data['last_updated_by'] = auth()->id();

        if (isset($data['mind_map_problem'])) {
            $data['mind_map_problem'] = $data['mind_map_problem']
                ->store('project-proposals/mind-map-problem', 'public');
        }
        if (isset($data['mind_map_solution'])) {
            $data['mind_map_solution'] = $data['mind_map_solution']
                ->store('project-proposals/mind-map-solution', 'public');
        }

        $data['status'] = $data['status'] ?? 'draft';
        $data['last_update'] = now();

        $proposal = $this->repository->create($data);

        broadcast(new ProjectProposalChanged($proposal, 'created'))->toOthers();

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Project proposal created successfully',
            201
        );
    }

    public function show(ProjectProposal $projectProposal)
    {
        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($projectProposal->load(['team', 'committeeReviews.committeeMember']))],
            'Project proposal retrieved successfully'
        );
    }

    public function showByTeam(ProjectTeam $projectTeam)
    {
        $proposal = ProjectProposal::where('project_team_id', $projectTeam->id)
            ->with(['team', 'committeeReviews.committeeMember'])
            ->first();

        return $this->successResponse(
            ['proposal' => $proposal ? new ProjectProposalResource($proposal) : null],
            'Project proposal retrieved successfully'
        );
    }

    public function update(ProjectProposal $projectProposal, array $data)
    {
        $data['last_updated_by'] = auth()->id();

        if (isset($data['mind_map_problem']) && $data['mind_map_problem'] instanceof UploadedFile) {
            if ($projectProposal->mind_map_problem) {
                Storage::disk('public')->delete($projectProposal->mind_map_problem);
            }
            $data['mind_map_problem'] = $data['mind_map_problem']
                ->store('project-proposals/mind-map-problem', 'public');
        } else {
            unset($data['mind_map_problem']);
        }

        if (isset($data['mind_map_solution']) && $data['mind_map_solution'] instanceof UploadedFile) {
            if ($projectProposal->mind_map_solution) {
                Storage::disk('public')->delete($projectProposal->mind_map_solution);
            }
            $data['mind_map_solution'] = $data['mind_map_solution']
                ->store('project-proposals/mind-map-solution', 'public');
        } else {
            unset($data['mind_map_solution']);
        }

        $data['last_update'] = now();

        $proposal = $this->repository->update($projectProposal, $data);
        $proposal->load(['team', 'committeeReviews.committeeMember']);

        broadcast(new ProjectProposalChanged($proposal, 'updated'))->toOthers();

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Project proposal updated successfully'
        );
    }

    public function destroy(ProjectProposal $projectProposal)
    {
        broadcast(new ProjectProposalChanged($projectProposal, 'deleted'))->toOthers();
        $this->repository->delete($projectProposal);

        return $this->successResponse(
            null,
            'Project proposal deleted successfully'
        );
    }

    public function submitToSupervisor(ProjectProposal $projectProposal, array $data): JsonResponse
    {
        if ($projectProposal->team->leader_id !== auth()->id()) {
            return $this->errorResponse('Only team leader can submit the proposal.', null, 403);
        }
        if ($projectProposal->supervisor_id == $data['supervisor_id'] && $projectProposal->status === 'submitted') {
            return $this->errorResponse(
                'This proposal has already been submitted to this supervisor.',
                null,
                422
            );
        }

        $updateData = [
            'status' => 'submitted',
            'supervisor_id' => $data['supervisor_id'],
            'supervisor' => $data['supervisor_id'],
            'last_updated_by' => auth()->id(),
            'last_update' => now(),
        ];

        $proposal = $this->repository->update($projectProposal, $updateData);
        $proposal->load(['team.projectIdea', 'supervisorUser']);

        broadcast(new ProjectProposalChanged($proposal, 'submitted'))->toOthers();
        $this->notifySupervisorAboutProposalSubmission($proposal);

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Submitted to supervisor.'
        );
    }

    public function handleDecision(ProjectProposal $projectProposal, array $data): JsonResponse
    {
        if ((int) $projectProposal->supervisor !== auth()->id()) {
            return $this->errorResponse('Access denied.', null, 403);
        }

        $mappedStatus = match ($data['status']) {
            'approved', 'supervisor_approved' => 'supervisor_approved',
            'rejected', 'supervisor_rejected' => 'supervisor_rejected',
            'needs_revision' => 'needs_revision',
        };

        $updateData = ['status' => $mappedStatus];
        if (Schema::hasColumn('project_proposals', 'supervisor_notes')) {
            $updateData['supervisor_notes'] = $data['notes'] ?? null;
        }
        if (Schema::hasColumn('project_proposals', 'supervisor_decided_at')) {
            $updateData['supervisor_decided_at'] = now();
        }
        if (Schema::hasColumn('project_proposals', 'last_update')) {
            $updateData['last_update'] = now();
        }

        $proposal = $this->repository->update($projectProposal, $updateData);
        $proposal->load(['team.members.user', 'team.leader', 'team.projectIdea', 'committeeReviews.committeeMember']);

        broadcast(new ProjectProposalChanged($proposal, 'supervisor_decision'))->toOthers();
        $this->notifyTeamAboutSupervisorDecision($proposal, $data['notes'] ?? null);

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Decision saved.'
        );
    }

    public function submitToCommittee(ProjectProposal $projectProposal): JsonResponse
    {
        if ($projectProposal->status !== 'supervisor_approved') {
            return $this->errorResponse('Only approved proposals can be submitted.', null, 422);
        }

        $updateData = ['status' => 'submitted_to_committee'];
        if (Schema::hasColumn('project_proposals', 'last_update')) {
            $updateData['last_update'] = now();
        }

        $proposal = $this->repository->update($projectProposal, $updateData);
        $proposal->load(['team.projectIdea', 'committeeReviews.committeeMember']);

        broadcast(new ProjectProposalChanged($proposal, 'submitted_to_committee'))->toOthers();
        $this->notifyCommitteeAboutProposal($proposal);

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Submitted to committee.'
        );
    }

    public function getSupervisorIncomingProposals(): JsonResponse
    {
        $proposals = $this->repository->getForSupervisor(auth()->id());

        return $this->successResponse(
            ProjectProposalResource::collection($proposals),
            'Supervisor proposals retrieved.'
        );
    }

    public function getApprovedProposalsForSupervisor(): JsonResponse
    {
        $proposals = ProjectProposal::where('supervisor_id', auth()->id())
            ->whereIn('status', [
                'supervisor_approved',
                'submitted_to_committee',
                'committee_approved',
                'committee_rejected',
                'committee_needs_revision',
            ])
            ->with(['team.projectIdea', 'team.members.user', 'team.leader', 'creator', 'supervisorUser'])
            ->latest('supervisor_decided_at')
            ->paginate(10);

        return $this->paginatedResponse(
            ProjectProposalResource::collection($proposals),
            $proposals,
            'Approved proposals retrieved successfully.'
        );
    }

    private function getUserTeam(): ?ProjectTeam
    {
        $user = auth()->id();

        return ProjectTeam::where('leader_id', $user)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user))
            ->first();
    }

    private function notifySupervisorAboutProposalSubmission(ProjectProposal $proposal): void
    {
        if (! $proposal->supervisorUser || $proposal->supervisorUser->id === auth()->id()) {
            return;
        }

        $this->notifications->sendToUser($proposal->supervisorUser, 'Supervisor request received', "A proposal was submitted for your review: {$proposal->title}.", [
            'type' => NotificationType::SupervisorRequestSubmitted->value,
            'entity_type' => 'project_proposal',
            'entity_id' => $proposal->id,
            'proposal_id' => $proposal->id,
            'team_id' => $proposal->project_team_id,
            'project_idea_id' => $proposal->team?->project_idea_id,
            'status' => $proposal->status,
            'action_url' => '/supervisor/project-proposals',
        ]);
    }

    private function notifyTeamAboutSupervisorDecision(ProjectProposal $proposal, ?string $notes): void
    {
        $recipients = $this->teamRecipients($proposal->team, auth()->id());

        if ($recipients->isEmpty()) {
            return;
        }

        [$type, $title, $body] = match ($proposal->status) {
            'supervisor_approved' => [
                NotificationType::SupervisorProposalApproved->value,
                'Proposal approved by supervisor',
                "Your proposal '{$proposal->title}' was approved by the supervisor.",
            ],
            'supervisor_rejected' => [
                NotificationType::SupervisorProposalRejected->value,
                'Proposal rejected by supervisor',
                "Your proposal '{$proposal->title}' was rejected by the supervisor.",
            ],
            default => [
                NotificationType::SupervisorProposalNeedsRevision->value,
                'Proposal needs revision',
                "Your proposal '{$proposal->title}' needs revisions from the supervisor.",
            ],
        };

        $this->notifications->sendToUsers($recipients, $title, $body, [
            'type' => $type,
            'entity_type' => 'project_proposal',
            'entity_id' => $proposal->id,
            'proposal_id' => $proposal->id,
            'team_id' => $proposal->project_team_id,
            'project_idea_id' => $proposal->team?->project_idea_id,
            'status' => $proposal->status,
            'notes' => $notes,
            'action_url' => '/project-proposals/'.$proposal->id,
        ]);
    }

    private function notifyCommitteeAboutProposal(ProjectProposal $proposal): void
    {
        $this->notifications->sendToUsers(
            $this->users->getActiveUsersByRole(UserRole::CommitteeMember->value),
            'Proposal submitted to committee',
            "A proposal is ready for committee review: {$proposal->title}.",
            [
                'type' => NotificationType::ProposalSubmittedToCommittee->value,
                'entity_type' => 'project_proposal',
                'entity_id' => $proposal->id,
                'proposal_id' => $proposal->id,
                'team_id' => $proposal->project_team_id,
                'project_idea_id' => $proposal->team?->project_idea_id,
                'status' => $proposal->status,
                'action_url' => '/committee/project-proposals',
            ]
        );
    }

    private function teamRecipients(?ProjectTeam $team, ?int $excludeUserId = null): Collection
    {
        if (! $team) {
            return collect();
        }

        $team->loadMissing(['members.user', 'leader']);
        $recipients = $team->members->pluck('user')->filter();

        if ($team->leader) {
            $recipients->push($team->leader);
        }

        return $recipients
            ->filter(fn ($user): bool => $user && (int) $user->id !== (int) $excludeUserId)
            ->unique('id')
            ->values();
    }
}
