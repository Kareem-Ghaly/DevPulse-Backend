<?php

namespace App\Services;

use App\Events\ProjectProposalChanged;
use App\Http\Resources\ProjectProposalResource;
use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Interfaces\ProjectTeamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\ProjectProposal;
use App\Models\ProjectTeam;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProjectProposalService
{
    use ApiResponse;

    private const NO_TEAM_MESSAGE = 'You must belong to a project team before creating or submitting a proposal.';

    private const MULTIPLE_TEAMS_MESSAGE = 'You belong to multiple project teams. Please specify or select the active team.';

    private const UNLINKED_REVIEW_MESSAGE = 'This proposal is not linked to a project team and cannot be reviewed.';

    public function __construct(
        private readonly ProjectProposalRepositoryInterface $repository,
        private readonly ProjectTeamRepositoryInterface $projectTeams,
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
        [$team, $teamError] = $this->resolveUniqueTeamForUser(auth()->id());

        if ($teamError) {
            return $this->errorResponse($teamError, null, 422);
        }

        unset($data['project_team_id']);

        $data['project_team_id'] = $team->id;
        $this->setTrackingFields($data, creating: true);

        if (! $this->normalizeSupervisorFields($data)) {
            return $this->errorResponse('The selected supervisor must have the Supervisor role.', null, 422);
        }

        $this->storeUploadedMindMaps($data);

        $data['status'] = $data['status'] ?? 'draft';
        $data['last_update'] = now();

        $proposal = $this->repository->create($data);

        $this->broadcastProposalChange($proposal, 'created');

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Project proposal created successfully',
            201
        );
    }

    public function show(ProjectProposal $projectProposal)
    {
        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($projectProposal->load(['team', 'supervisorUser', 'creator', 'lastUpdater', 'committeeReviews.committeeMember']))],
            'Project proposal retrieved successfully'
        );
    }

    public function update(ProjectProposal $projectProposal, array $data)
    {
        unset($data['project_team_id']);

        [$team, $teamError] = $this->ensureProposalTeam($projectProposal);

        if ($teamError) {
            return $this->errorResponse($teamError, null, 422);
        }

        if (! $this->proposalHasValidTeam($projectProposal)) {
            $data['project_team_id'] = $team->id;
        }

        $this->setTrackingFields($data);

        if (! $this->normalizeSupervisorFields($data)) {
            return $this->errorResponse('The selected supervisor must have the Supervisor role.', null, 422);
        }

        $this->storeUploadedMindMaps($data);

        $data['last_update'] = now();

        $previousStatus = $projectProposal->status;
        $proposal = $this->repository->update($projectProposal, $data);

        $this->broadcastProposalChange($proposal, 'updated');
        $this->notifyProposalUpdatedWhenSubmitted($proposal, $previousStatus);

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Project proposal updated successfully'
        );
    }

    public function destroy(ProjectProposal $projectProposal)
    {
        $this->broadcastProposalChange($projectProposal, 'deleted');

        $this->repository->delete($projectProposal);

        return $this->successResponse(
            null,
            'Project proposal deleted successfully'
        );
    }

    public function submitToSupervisor(ProjectProposal $projectProposal, array $data): JsonResponse
    {
        [$team, $teamError] = $this->ensureProposalTeam($projectProposal);

        if ($teamError) {
            return $this->errorResponse($teamError, null, 422);
        }

        $supervisor = $this->supervisorFromPayload($data);

        if (! $supervisor) {
            return $this->errorResponse('The selected supervisor must have the Supervisor role.', null, 422);
        }

        $updateData = [
            'status' => 'submitted',
            'last_update' => now(),
        ];

        if (! $this->proposalHasValidTeam($projectProposal)) {
            $updateData['project_team_id'] = $team->id;
        }

        $this->setTrackingFields($updateData);
        $this->setSupervisorFields($updateData, $supervisor);

        $proposal = $this->repository->update($projectProposal, $updateData);

        $this->broadcastProposalChange($proposal, 'submitted');
        $this->notifyProposalSubmittedToSupervisor($proposal);

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'The project proposal has been linked and officially submitted to the supervisor.'
        );
    }

    public function handleDecision(ProjectProposal $projectProposal, array $data): JsonResponse
    {
        if (! (auth()->user()?->hasRole('Supervisor') ?? false)) {
            return $this->errorResponse('Access denied. Only supervisors can decide proposals.', null, 403);
        }

        if ((int) $projectProposal->supervisor_id !== auth()->id()) {
            return $this->errorResponse(
                'Access denied. You are not the assigned supervisor for this proposal.',
                null,
                403
            );
        }

        [$team, $teamError] = $this->ensureProposalTeam($projectProposal);

        if ($teamError) {
            return $this->errorResponse(self::UNLINKED_REVIEW_MESSAGE, null, 422);
        }

        $mappedStatus = match ($data['status']) {
            'approved', 'supervisor_approved' => 'supervisor_approved',
            'rejected', 'supervisor_rejected' => 'supervisor_rejected',
            'changes_requested', 'needs_revision' => 'needs_revision',
        };

        $updateData = [
            'status' => $mappedStatus,
        ];

        if (! $this->proposalHasValidTeam($projectProposal)) {
            $updateData['project_team_id'] = $team->id;
        }

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
        $proposal->load(['team', 'supervisorUser', 'creator', 'lastUpdater', 'committeeReviews.committeeMember']);

        $this->broadcastProposalChange($proposal, 'supervisor_decision');
        $this->notifySupervisorDecision($proposal);

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Supervisor decision saved successfully.'
        );
    }

    public function submitToCommittee(ProjectProposal $projectProposal): JsonResponse
    {
        if ($projectProposal->status !== 'supervisor_approved') {
            return $this->errorResponse(
                'Only supervisor-approved proposals can be submitted to the committee.',
                null,
                422
            );
        }

        [$team, $teamError] = $this->ensureProposalTeam($projectProposal);

        if ($teamError) {
            return $this->errorResponse('This proposal is not linked to a project team and cannot be submitted.', null, 422);
        }

        $updateData = ['status' => 'submitted_to_committee'];

        if (! $this->proposalHasValidTeam($projectProposal)) {
            $updateData['project_team_id'] = $team->id;
        }

        if (Schema::hasColumn('project_proposals', 'last_update')) {
            $updateData['last_update'] = now();
        }

        $proposal = $this->repository->update($projectProposal, $updateData);
        $proposal->load(['team', 'supervisorUser', 'creator', 'lastUpdater', 'committeeReviews.committeeMember']);

        $this->broadcastProposalChange($proposal, 'submitted_to_committee');

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Project proposal submitted to committee successfully.'
        );
    }

    public function getSupervisorIncomingProposals(): JsonResponse
    {
        $proposals = $this->repository->getForSupervisor(auth()->id());

        return $this->successResponse(
            ProjectProposalResource::collection($proposals),
            'Supervisor incoming project proposals retrieved successfully.'
        );
    }

    private function ensureProposalTeam(ProjectProposal $projectProposal): array
    {
        if ($this->proposalHasValidTeam($projectProposal)) {
            return [$this->projectTeams->findById((int) $projectProposal->project_team_id), null];
        }

        foreach ([$projectProposal->created_by, $projectProposal->last_updated_by, auth()->id()] as $userId) {
            [$team, $error] = $this->resolveUniqueTeamForUser($userId);

            if ($team) {
                return [$team, null];
            }

            if ($error === self::MULTIPLE_TEAMS_MESSAGE) {
                return [null, $error];
            }
        }

        return [null, self::NO_TEAM_MESSAGE];
    }

    private function resolveUniqueTeamForUser(?int $userId): array
    {
        if (! $userId) {
            return [null, self::NO_TEAM_MESSAGE];
        }

        $teams = $this->projectTeams->getForUser($userId);

        if ($teams->isEmpty()) {
            return [null, self::NO_TEAM_MESSAGE];
        }

        if ($teams->count() > 1) {
            return [null, self::MULTIPLE_TEAMS_MESSAGE];
        }

        return [$teams->first(), null];
    }

    private function proposalHasValidTeam(ProjectProposal $projectProposal): bool
    {
        return $projectProposal->project_team_id !== null
            && $this->projectTeams->findById((int) $projectProposal->project_team_id) !== null;
    }

    private function normalizeSupervisorFields(array &$data): bool
    {
        $supervisorWasProvided = array_key_exists('supervisor_id', $data) || array_key_exists('supervisor', $data);
        $supervisor = $this->supervisorFromPayload($data);

        if ($supervisor) {
            $this->setSupervisorFields($data, $supervisor);

            return true;
        }

        if (array_key_exists('supervisor_id', $data)) {
            unset($data['supervisor_id']);
        }

        return ! $supervisorWasProvided;
    }

    private function supervisorFromPayload(array $data): ?User
    {
        $supervisorId = $data['supervisor_id'] ?? null;

        if ($supervisorId) {
            return $this->users->findSupervisorById((int) $supervisorId);
        }

        $legacySupervisor = isset($data['supervisor']) ? trim((string) $data['supervisor']) : '';

        if ($legacySupervisor === '') {
            return null;
        }

        if (is_numeric($legacySupervisor)) {
            return $this->users->findSupervisorById((int) $legacySupervisor);
        }

        if (filter_var($legacySupervisor, FILTER_VALIDATE_EMAIL)) {
            return $this->users->findSupervisorByEmail($legacySupervisor);
        }

        return $this->users->findSupervisorByExactName($legacySupervisor);
    }

    private function setSupervisorFields(array &$data, User $supervisor): void
    {
        if (Schema::hasColumn('project_proposals', 'supervisor_id')) {
            $data['supervisor_id'] = $supervisor->id;
        }

        $data['supervisor'] = $supervisor->name;
    }

    private function setTrackingFields(array &$data, bool $creating = false): void
    {
        $userId = auth()->id();

        if ($creating && Schema::hasColumn('project_proposals', 'created_by')) {
            $data['created_by'] = $userId;
        }

        if (Schema::hasColumn('project_proposals', 'last_updated_by')) {
            $data['last_updated_by'] = $userId;
        }
    }

    private function storeUploadedMindMaps(array &$data): void
    {
        if (isset($data['mind_map_problem'])) {
            $data['mind_map_problem'] = $data['mind_map_problem']
                ->store('project-proposals/mind-map-problem', 'public');
        }

        if (isset($data['mind_map_solution'])) {
            $data['mind_map_solution'] = $data['mind_map_solution']
                ->store('project-proposals/mind-map-solution', 'public');
        }
    }

    private function notifyProposalSubmittedToSupervisor(ProjectProposal $proposal): void
    {
        $proposal->loadMissing('supervisorUser');

        if (! $proposal->supervisorUser) {
            return;
        }

        $this->notifications->sendToUser($proposal->supervisorUser, 'Project proposal submitted', "A project proposal was submitted for your review: {$proposal->title}.", [
            'type' => 'proposal_submitted_to_supervisor',
            'entity_type' => 'project_proposal',
            'entity_id' => $proposal->id,
        ]);
    }

    private function notifyProposalUpdatedWhenSubmitted(ProjectProposal $proposal, ?string $previousStatus): void
    {
        if ($previousStatus === 'draft' || ! in_array($proposal->status, ['submitted', 'needs_revision'], true)) {
            return;
        }

        $proposal->loadMissing('supervisorUser');

        if (! $proposal->supervisorUser) {
            return;
        }

        $this->notifications->sendToUser($proposal->supervisorUser, 'Project proposal updated', "A submitted project proposal was updated: {$proposal->title}.", [
            'type' => 'proposal_updated',
            'entity_type' => 'project_proposal',
            'entity_id' => $proposal->id,
        ]);
    }

    private function notifySupervisorDecision(ProjectProposal $proposal): void
    {
        $type = match ($proposal->status) {
            'supervisor_approved' => 'proposal_approved_by_supervisor',
            'needs_revision' => 'proposal_changes_requested_by_supervisor',
            'supervisor_rejected' => 'proposal_rejected_by_supervisor',
            default => null,
        };

        if (! $type) {
            return;
        }

        $proposal->loadMissing(['team.members.user', 'team.leader']);

        if (! $proposal->team) {
            return;
        }

        $members = $proposal->team->members->pluck('user')->filter();

        if ($proposal->team->leader) {
            $members->push($proposal->team->leader);
        }

        $this->notifications->sendToUsers($members, 'Project proposal decision', "Supervisor decision was saved for: {$proposal->title}.", [
            'type' => $type,
            'entity_type' => 'project_proposal',
            'entity_id' => $proposal->id,
        ]);
    }
    private function broadcastProposalChange(ProjectProposal $proposal, string $action): void
    {
        try {
            ProjectProposalChanged::dispatch($proposal, $action);
        } catch (Throwable $e) {
            Log::warning('Project proposal broadcast failed', [
                'proposal_id' => $proposal->id ?? null,
                'action' => $action,
                'message' => $e->getMessage(),
            ]);
        }
    }
}