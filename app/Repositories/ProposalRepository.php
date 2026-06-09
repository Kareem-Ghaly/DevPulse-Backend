<?php

namespace App\Repositories;

use App\Interfaces\ProposalRepositoryInterface;
use App\Models\Proposal;

class ProposalRepository implements ProposalRepositoryInterface
{
    public function firstOrCreate(array $attributes, array $values): Proposal
    {
        return Proposal::query()->firstOrCreate($attributes, $values);
    }
}
