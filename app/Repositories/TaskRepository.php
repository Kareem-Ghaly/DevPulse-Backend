<?php

namespace App\Repositories;

use App\Interfaces\TaskRepositoryInterface;
use App\Models\ProjectTeam;
use App\Models\Task;
use Illuminate\Support\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    public function getForTeam(ProjectTeam $projectTeam): Collection
    {
        return Task::query()
            ->with(['assignedUser', 'creator', 'completedBy', 'attachments.uploader', 'links.creator'])
            ->where('project_team_id', $projectTeam->id)
            ->orderByDesc('last_update')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getForSupervisorTeam(ProjectTeam $projectTeam): Collection
    {
        return Task::query()
            ->with(['assignedUser', 'creator', 'attachments.uploader', 'links.creator', 'latestReview.supervisor'])
            ->where('project_team_id', $projectTeam->id)
            ->orderByDesc('last_update')
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): Task
    {
        return Task::query()->create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh(['assignedUser', 'creator', 'completedBy', 'attachments.uploader', 'links.creator']);
    }

    public function delete(Task $task): bool
    {
        return $task->delete();
    }
}

