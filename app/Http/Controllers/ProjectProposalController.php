<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectProposalRequest;
use App\Http\Requests\UpdateProjectProposalRequest;
use App\Models\ProjectProposal;
use App\Services\ProjectProposalService;

class ProjectProposalController extends Controller
{
    public function __construct(private readonly ProjectProposalService $service) {}

    public function index()
    {
        return $this->service->index();
    }

    public function store(StoreProjectProposalRequest $request)
    {
        return $this->service->store($request->validated());
    }

    public function show(ProjectProposal $projectProposal)
    {
        return $this->service->show($projectProposal);
    }

    public function update(UpdateProjectProposalRequest $request, ProjectProposal $projectProposal)
    {
        return $this->service->update($projectProposal, $request->validated());
    }

    public function destroy(ProjectProposal $projectProposal)
    {
        return $this->service->destroy($projectProposal);
    }
}