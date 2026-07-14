<?php

namespace App\Repositories;

use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Models\ProjectProposal;
use Illuminate\Support\Collection;

class ProjectProposalRepository implements ProjectProposalRepositoryInterface
{
    public function all()
    {
        return ProjectProposal::with(['team', 'supervisorUser', 'creator', 'lastUpdater'])->latest()->get();
    }

    public function find(int $id): ?ProjectProposal
    {
        return ProjectProposal::with(['team', 'supervisorUser', 'creator', 'lastUpdater'])->find($id);
    }

    public function create(array $data): ProjectProposal
    {
        return ProjectProposal::create($data)->load(['team', 'supervisorUser', 'creator', 'lastUpdater']);
    }

    public function update(ProjectProposal $projectProposal, array $data): ProjectProposal
    {
        $projectProposal->update($data);

        return $projectProposal->fresh(['team', 'supervisorUser', 'creator', 'lastUpdater']);
    }

    public function delete(ProjectProposal $projectProposal): bool
    {
        return $projectProposal->delete();
    }

    public function getForSupervisor(int $supervisorId): Collection
    {
        return ProjectProposal::query()
            ->with(['team', 'supervisorUser', 'creator', 'lastUpdater'])
            ->where('supervisor_id', $supervisorId)
            ->where('status', 'submitted')
            ->orderByDesc('last_update')
            ->get();
    }

    public function getForCommittee(): Collection
    {
        return ProjectProposal::query()
            ->with(['team', 'supervisorUser', 'committeeReviews.committeeMember'])
            ->where('status', 'submitted_to_committee')
            ->orderByDesc('last_update')
            ->get();
    }

    public function findByTeamAndSupervisor(int $projectTeamId, int $supervisorId): ?ProjectProposal
    {
        return ProjectProposal::query()
            ->with(['team', 'supervisorUser', 'creator', 'lastUpdater'])
            ->where('project_team_id', $projectTeamId)
            ->where('supervisor_id', $supervisorId)
            ->first();
    }

    public function findByTeam(int $projectTeamId): ?ProjectProposal
    {
        return ProjectProposal::query()
            ->with(['team', 'supervisorUser', 'creator', 'lastUpdater'])
            ->where('project_team_id', $projectTeamId)
            ->first();
    }
}