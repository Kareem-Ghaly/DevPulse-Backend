<?php

namespace App\Interfaces;

use App\Models\Task;
use App\Models\TaskReview;

interface TaskReviewRepositoryInterface
{
    public function findByTaskAndSupervisor(int $taskId, int $supervisorId): ?TaskReview;

    public function updateOrCreateForSupervisor(Task $task, int $supervisorId, string $review): TaskReview;
}
