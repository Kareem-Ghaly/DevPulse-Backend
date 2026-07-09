<?php

namespace App\Services;

use App\Events\ProjectProposalChanged;
use App\Http\Resources\ProjectProposalResource;
use App\Interfaces\ProjectProposalCommitteeReviewRepositoryInterface;
use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Models\ProjectProposal;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CommitteeProjectProposalService
{
    use ApiResponse;

    public function __construct(
        private readonly ProjectProposalRepositoryInterface $projectProposals,
        private readonly ProjectProposalCommitteeReviewRepositoryInterface $committeeReviews
    ) {}

    public function index(): JsonResponse
    {
        $proposals = $this->projectProposals->getForCommittee();

        return $this->successResponse(
            ProjectProposalResource::collection($proposals),
            'Committee project proposals retrieved successfully.'
        );
    }

    public function decision(ProjectProposal $projectProposal, array $data): JsonResponse
    {
        if (! in_array($projectProposal->status, ['submitted_to_committee', 'committee_needs_revision'], true)) {
            return $this->errorResponse(
                'Committee decisions can only be made for proposals submitted to the committee or needing revision.',
                null,
                422
            );
        }

        $review = $this->committeeReviews->create([
            'project_proposal_id' => $projectProposal->id,
            'committee_member_id' => auth()->id(),
            'decision' => $data['decision'],
            'notes' => $data['notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        $status = match ($data['decision']) {
            'approved' => 'committee_approved',
            'rejected' => 'committee_rejected',
            'needs_revision' => 'committee_needs_revision',
        };

        $updateData = ['status' => $status];

        if (Schema::hasColumn('project_proposals', 'last_update')) {
            $updateData['last_update'] = now();
        }

        $proposal = $this->projectProposals->update($projectProposal, $updateData);
        $proposal->load(['team', 'committeeReviews.committeeMember']);

        $this->broadcastProposalChange($proposal, 'committee_decision');

        return $this->successResponse(
            [
                'proposal' => new ProjectProposalResource($proposal),
                'review' => $review->load('committeeMember'),
            ],
            'Committee decision saved successfully.'
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
