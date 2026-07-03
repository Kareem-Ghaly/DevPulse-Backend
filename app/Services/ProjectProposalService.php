<?php

namespace App\Services;

use App\Http\Resources\ProjectProposalResource;
use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Models\ProjectProposal;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

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

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Project proposal created successfully',
            201
        );
    }

    public function show(ProjectProposal $projectProposal)
    {
        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($projectProposal->load('team'))],
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

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'Project proposal updated successfully'
        );
    }

    public function destroy(ProjectProposal $projectProposal)
    {
        $this->repository->delete($projectProposal);

        return $this->successResponse(
            null,
            'Project proposal deleted successfully'
        );
    }


    public function handleDecision(ProjectProposal $projectProposal, array $data): JsonResponse
    {
        if ((int)$projectProposal->supervisor !== auth()->id()) {
            return $this->errorResponse('Access denied. You are not the assigned supervisor for this proposal.', null, 403);
        }

        $status = $data['status'];
        $notes = $data['notes'] ?? null;

        $updateData = [
            'status' => $status,
            'notes' => $notes, 
            'last_updated_by' => auth()->id(),
            'last_update' => now(),
        ];

        $proposal = $this->repository->update($projectProposal, $updateData);

        $message = "Proposal status has been updated to {$status}.";
        if ($status === 'approved') {
            $message = "Congratulations! You are now the official supervisor for this project team.";
        }

        return $this->successResponse(['proposal' => new ProjectProposalResource($proposal)], $message);
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

        return $this->successResponse(
            ['proposal' => new ProjectProposalResource($proposal)],
            'The project proposal has been linked and officially submitted to the supervisor.'
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
}