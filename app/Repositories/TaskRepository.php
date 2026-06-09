<?php

namespace App\Repositories;

use App\Interfaces\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Support\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    public function getByProject(int $projectId): Collection
    {
        return Task::query()
            ->with('assignee')
            ->where('project_id', $projectId)
            ->latest()
            ->get();
    }
}
