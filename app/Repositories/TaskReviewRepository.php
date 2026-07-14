<?php

namespace App\Repositories;

use App\Interfaces\TaskReviewRepositoryInterface;
use App\Models\Task;
use App\Models\TaskReview;

class TaskReviewRepository implements TaskReviewRepositoryInterface
{
    public function findByTaskAndSupervisor(int $taskId, int $supervisorId): ?TaskReview
    {
        return TaskReview::query()
            ->where('task_id', $taskId)
            ->where('supervisor_id', $supervisorId)
            ->first();
    }

    public function updateOrCreateForSupervisor(Task $task, int $supervisorId, string $review): TaskReview
    {
        return TaskReview::query()->updateOrCreate(
            [
                'task_id' => $task->id,
                'supervisor_id' => $supervisorId,
            ],
            [
                'review' => $review,
                'reviewed_at' => now(),
            ],
        );
    }
}
