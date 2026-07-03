<?php

namespace App\Repositories;

use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Models\ProjectProposal;

class ProjectProposalRepository implements ProjectProposalRepositoryInterface
{
    public function all()
    {
        return ProjectProposal::with('team')->latest()->get();
    }

    public function find(int $id): ?ProjectProposal
    {
        return ProjectProposal::with('team')->find($id);
    }

    public function create(array $data): ProjectProposal
    {
        return ProjectProposal::create($data);
    }

    public function update(ProjectProposal $projectProposal, array $data): ProjectProposal
    {
        $projectProposal->update($data);

        return $projectProposal->fresh('team');
    }

    public function delete(ProjectProposal $projectProposal): bool
    {
        return $projectProposal->delete();
    }

    public function getForSupervisor(int $supervisorId): \Illuminate\Support\Collection
    {
        return \App\Models\ProjectProposal::query()
            ->where('supervisor', $supervisorId)
            ->whereIn('status', ['submitted', 'approved', 'rejected', 'changes_requested'])
            ->orderByDesc('last_update')
            ->get();
    }
}