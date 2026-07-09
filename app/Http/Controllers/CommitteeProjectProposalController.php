<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommitteeDecisionRequest;
use App\Models\ProjectProposal;
use App\Services\CommitteeProjectProposalService;

class CommitteeProjectProposalController extends Controller
{
    public function __construct(private readonly CommitteeProjectProposalService $service) {}

    public function index()
    {
        return $this->service->index();
    }

    public function decision(CommitteeDecisionRequest $request, ProjectProposal $projectProposal)
    {
        return $this->service->decision($projectProposal, $request->validated());
    }
}