<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectProposalRequest;
use App\Http\Requests\SupervisorDecisionRequest;
use App\Http\Requests\UpdateProjectProposalRequest;
use App\Models\ProjectProposal;
use App\Services\ProjectProposalService;
use Illuminate\Http\Request;

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

    public function submitToSupervisor(Request $request, ProjectProposal $projectProposal)
    {
        $validated = $request->validate([
            'supervisor_id' => ['required', 'exists:users,id'],
        ]);

        return $this->service->submitToSupervisor($projectProposal, $validated);
    }

    public function submitToCommittee(ProjectProposal $projectProposal)
    {
        return $this->service->submitToCommittee($projectProposal);
    }

    public function supervisorIncoming(Request $request)
    {
        if (! auth()->user()->hasRole('Supervisor')) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        return $this->service->getSupervisorIncomingProposals();
    }

    public function supervisorDecision(SupervisorDecisionRequest $request, ProjectProposal $projectProposal)
    {
        return $this->service->handleDecision($projectProposal, $request->validated());
    }
}