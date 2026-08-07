<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectProposalRequest;
use App\Http\Requests\SupervisorDecisionRequest;
use App\Http\Requests\UpdateProjectProposalRequest;
use App\Models\ProjectProposal;
use App\Models\ProjectTeam;
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

    public function showByTeam(ProjectTeam $projectTeam)
    {
        return $this->service->showByTeam($projectTeam);
    }

    public function update(Request $request, ProjectProposal $projectProposal)
{
    \Log::info('Request all:', $request->all());
    \Log::info('Has file mind_map_problem:', [$request->hasFile('mind_map_problem')]);
    \Log::info('File info:', $request->file('mind_map_problem') ? [
        'name' => $request->file('mind_map_problem')->getClientOriginalName(),
        'size' => $request->file('mind_map_problem')->getSize(),
        'mime' => $request->file('mind_map_problem')->getMimeType(),
    ] : ['no file']);
    
    return $this->service->update($projectProposal, $request->all());
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
    
    public function approvedProposals()
    {
        return $this->service->getApprovedProposalsForSupervisor();
    }
}