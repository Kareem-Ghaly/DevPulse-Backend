<?php

namespace App\Services;

use App\Events\ProjectProposalChanged;
use App\Http\Resources\ProjectProposalResource;
use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Models\ProjectProposal;
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
            ['proposal' => new ProjectProposalResource($projectProposal->load(['team', 'committeeReviews.committeeMember']))],
            'Project proposal retrieved successfully'
        );
    }

    public function update(ProjectProposal $projectProposal, array $data)
    {
        if (isset($data['mind_map_problem'])) {
            $data['mind_map_problem'] = $data['mind_map_problem']
                ->store('project-proposals/mind-map-problem', 'public');
        }

        if (isset($data['mind_map_solution'])) {
            $data['mind_map_solution'] = $data['mind_map_solution']
                ->store('project-proposals/mind-map-solution', 'public');
        }

        $data['last_update'] = now();

        $proposal = $this->repository->update($projectProposal, $data);

        $this->broadcastProposalChange($proposal, 'updated');

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
        $updateData = [
            'status' => 'submitted',
            'supervisor' => $data['supervisor_id'],
            'last_updated_by' => auth()->id(),
            'last_update' => now(),
        ];

        $proposal = $this->repository->update($projectProposal, $updateData);

        $this->broadcastProposalChange($proposal, 'submitted');

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'The project proposal has been linked and officially submitted to the supervisor.'
        );
    }

    public function handleDecision(ProjectProposal $projectProposal, array $data): JsonResponse
    {
        if ((int) $projectProposal->supervisor !== auth()->id()) {
            return $this->errorResponse(
                'Access denied. You are not the assigned supervisor for this proposal.',
                null,
                403
            );
        }

        $mappedStatus = match ($data['status']) {
            'approved', 'supervisor_approved' => 'supervisor_approved',
            'rejected', 'supervisor_rejected' => 'supervisor_rejected',
            'needs_revision' => 'needs_revision',
        };

        $updateData = [
            'status' => $mappedStatus,
        ];

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

        $this->broadcastProposalChange($proposal, 'supervisor_decision');

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

        $updateData = ['status' => 'submitted_to_committee'];

        if (Schema::hasColumn('project_proposals', 'last_update')) {
            $updateData['last_update'] = now();
        }

        $proposal = $this->repository->update($projectProposal, $updateData);
        $proposal->load(['team', 'committeeReviews.committeeMember']);

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
