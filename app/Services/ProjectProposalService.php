<?php

namespace App\Services;

use App\Events\ProjectProposalChanged;
use App\Http\Resources\ProjectProposalResource;
use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Models\ProjectProposal;
use App\Models\ProjectTeam;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProjectProposalService
{
    use ApiResponse;

    public function __construct(
        private readonly ProjectProposalRepositoryInterface $repository
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
        if ($team && !($team->leader_id === auth()->id() || $team->members()->where('user_id', auth()->id())->exists())) {
            $team = null;
        }
    } else {
        $team = $this->getUserTeam();
    }
    
    if (!$team) {
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
        \Log::info('Data received in service:', $data);
    \Log::info('Is UploadedFile:', [
        'problem' => isset($data['mind_map_problem']) ? get_class($data['mind_map_problem']) : 'not set'
    ]);
        $data['last_updated_by'] = auth()->id();

        if (isset($data['mind_map_problem']) && $data['mind_map_problem'] instanceof \Illuminate\Http\UploadedFile) {
            if ($projectProposal->mind_map_problem) {
                \Storage::disk('public')->delete($projectProposal->mind_map_problem);
            }
            $data['mind_map_problem'] = $data['mind_map_problem']
                ->store('project-proposals/mind-map-problem', 'public');
        } else {
            unset($data['mind_map_problem']);
        }

        if (isset($data['mind_map_solution']) && $data['mind_map_solution'] instanceof \Illuminate\Http\UploadedFile) {
            if ($projectProposal->mind_map_solution) {
                \Storage::disk('public')->delete($projectProposal->mind_map_solution);
            }
            $data['mind_map_solution'] = $data['mind_map_solution']
                ->store('project-proposals/mind-map-solution', 'public');
        } else {
            unset($data['mind_map_solution']);
        }

        $data['last_update'] = now();

        $proposal = $this->repository->update($projectProposal, $data);
        
        $proposal->refresh();
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

        broadcast(new ProjectProposalChanged($proposal, 'submitted'))->toOthers();

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
        $proposal->load(['team', 'committeeReviews.committeeMember']);

        broadcast(new ProjectProposalChanged($proposal, 'supervisor_decision'))->toOthers();

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
        $proposal->load(['team', 'committeeReviews.committeeMember']);

        broadcast(new ProjectProposalChanged($proposal, 'submitted_to_committee'))->toOthers();

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

    private function getUserTeam(): ?ProjectTeam
    {
        $user = auth()->id();
        return ProjectTeam::where('leader_id', $user)
            ->orWhereHas('members', fn($q) => $q->where('user_id', $user))
            ->first();
    }
}