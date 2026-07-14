<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskReviewRequest;
use App\Models\ProjectTeam;
use App\Models\Task;
use App\Services\SupervisorTaskReviewService;
use Illuminate\Http\JsonResponse;

class SupervisorTaskReviewController extends Controller
{
    public function __construct(private readonly SupervisorTaskReviewService $taskReviews) {}

    public function index(ProjectTeam $projectTeam): JsonResponse
    {
        return $this->taskReviews->index($projectTeam);
    }

    public function review(StoreTaskReviewRequest $request, Task $task): JsonResponse
    {
        return $this->taskReviews->review($task, $request->validated());
    }
}
