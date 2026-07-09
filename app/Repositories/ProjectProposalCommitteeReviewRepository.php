<?php

namespace App\Repositories;

use App\Interfaces\ProjectProposalCommitteeReviewRepositoryInterface;
use App\Models\ProjectProposalCommitteeReview;

class ProjectProposalCommitteeReviewRepository implements ProjectProposalCommitteeReviewRepositoryInterface
{
    public function create(array $data): ProjectProposalCommitteeReview
    {
        return ProjectProposalCommitteeReview::create($data);
    }
}