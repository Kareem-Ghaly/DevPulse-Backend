<?php

namespace App\Repositories;

use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Models\ProjectProposal;
use Illuminate\Support\Collection;

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

    public function getForSupervisor(int $supervisorId): Collection
    {
        return ProjectProposal::query()
            ->where('supervisor', $supervisorId)
            ->where('status', 'submitted')
            ->orderByDesc('last_update')
            ->get();
    }

    public function getForCommittee(): Collection
    {
        return ProjectProposal::query()
            ->with(['team', 'committeeReviews.committeeMember'])
            ->where('status', 'submitted_to_committee')
            ->orderByDesc('last_update')
            ->get();
    }
}