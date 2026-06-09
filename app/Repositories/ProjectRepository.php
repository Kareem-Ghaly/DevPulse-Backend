<?php

namespace App\Repositories;

use App\Interfaces\ProjectRepositoryInterface;
use App\Models\Project;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function findById(int $id): ?Project
    {
        return Project::query()
            ->with(['team.members.user', 'proposal', 'tasks.assignee'])
            ->find($id);
    }

    public function findByProjectIdea(int $projectIdeaId): ?Project
    {
        return Project::query()
            ->where('project_idea_id', $projectIdeaId)
            ->first();
    }

    public function create(array $data): Project
    {
        return Project::query()->create($data);
    }

    public function loadDetails(Project $project): Project
    {
        return $project->load([
            'team.members.user',
            'proposal',
            'tasks.assignee',
        ]);
    }
}
