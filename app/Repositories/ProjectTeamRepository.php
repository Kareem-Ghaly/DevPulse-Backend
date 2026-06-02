<?php

namespace App\Repositories;

use App\Interfaces\ProjectTeamRepositoryInterface;
use App\Models\ProjectTeam;

class ProjectTeamRepository implements ProjectTeamRepositoryInterface
{
    public function findByProjectIdea(int $projectIdeaId): ?ProjectTeam
    {
        return ProjectTeam::query()
            ->with(['leader', 'members.user', 'projectIdea'])
            ->where('project_idea_id', $projectIdeaId)
            ->first();
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
