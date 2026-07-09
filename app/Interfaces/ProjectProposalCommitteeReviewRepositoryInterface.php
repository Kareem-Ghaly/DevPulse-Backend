<?php

namespace App\Interfaces;

use App\Models\ProjectProposalCommitteeReview;

interface ProjectProposalCommitteeReviewRepositoryInterface
{
    public function create(array $data): ProjectProposalCommitteeReview;
}