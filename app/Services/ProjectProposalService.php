<?php

namespace App\Services;

use App\Http\Resources\ProjectProposalResource;
use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Models\ProjectProposal;
use App\Traits\ApiResponse;

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
}