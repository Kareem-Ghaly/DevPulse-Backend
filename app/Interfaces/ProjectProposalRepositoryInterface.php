<?php

namespace App\Interfaces;

use App\Models\ProjectProposal;

interface ProjectProposalRepositoryInterface
{
    public function all();

    public function find(int $id): ?ProjectProposal;

    public function create(array $data): ProjectProposal;

    public function update(ProjectProposal $projectProposal, array $data): ProjectProposal;

    public function delete(ProjectProposal $projectProposal): bool;

    public function getForSupervisor(int $supervisorId): \Illuminate\Support\Collection;
}