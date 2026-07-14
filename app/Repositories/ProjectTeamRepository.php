<?php

namespace App\Repositories;

use App\Interfaces\ProjectTeamRepositoryInterface;
use App\Models\ProjectTeam;
use Illuminate\Support\Collection;

class ProjectTeamRepository implements ProjectTeamRepositoryInterface
{
    public function findByProjectIdea(int $projectIdeaId): ?ProjectTeam
    {
        return ProjectTeam::query()
            ->with(['leader', 'members.user', 'projectIdea'])
            ->where('project_idea_id', $projectIdeaId)
            ->first();
    }

    public function findById(int $id): ?ProjectTeam
    {
        return ProjectTeam::query()
            ->with(['leader', 'members.user', 'projectIdea'])
            ->find($id);
    }

    public function findForUser(int $userId): ?ProjectTeam
    {
        $teams = $this->getForUser($userId);

        return $teams->count() === 1 ? $teams->first() : null;
    }

    public function getForUser(int $userId): Collection
    {
        return ProjectTeam::query()
            ->with(['leader', 'members.user', 'projectIdea'])
            ->where(function ($query) use ($userId): void {
                $query->where('leader_id', $userId)
                    ->orWhereHas('members', fn ($memberQuery) => $memberQuery->where('user_id', $userId));
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->unique('id')
            ->values();
    }

    public function create(array $data): ProjectTeam
    {
        return ProjectTeam::query()->create($data);
    }

    public function markCompleted(ProjectTeam $team): ProjectTeam
    {
        $team->update(['status' => 'completed']);

        return $team->fresh(['leader', 'members.user', 'projectIdea']);
    }

    public function countMembers(ProjectTeam $team): int
    {
        return $team->members()->count();
    }
}