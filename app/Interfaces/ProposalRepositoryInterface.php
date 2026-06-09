<?php

namespace App\Interfaces;

use App\Models\Proposal;

interface ProposalRepositoryInterface
{
    public function firstOrCreate(array $attributes, array $values): Proposal;
}
