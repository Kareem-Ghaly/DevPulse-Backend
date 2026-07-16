<?php

namespace App\Services;

use App\Events\TaskChanged;
use App\Http\Resources\TaskResource;
use App\Interfaces\TaskRepositoryInterface;
use App\Interfaces\TaskReviewRepositoryInterface;
use App\Models\ProjectProposal;
use App\Models\ProjectTeam;
use App\Models\Task;
use App\Models\TaskReview;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupervisorTaskReviewService
{
    use ApiResponse;

    private const STATUSES = ['backlog', 'todo', 'in_progress', 'done'];

    private const TASK_RELATIONS = ['assignedUser', 'creator', 'attachments.uploader', 'links.creator', 'latestReview.supervisor'];

    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskReviewRepositoryInterface $reviews,
        private readonly NotificationService $notifications,
    ) {}

    public function index(ProjectTeam $projectTeam): JsonResponse
    {
        $supervisorId = auth()->id();

        if (! $this->authenticatedUserIsSupervisor()) {
            return $this->errorResponse('Only supervisors can view supervised team tasks.', null, 403);
        }

        if (! $this->supervisorIsAssignedToTeam($projectTeam, $supervisorId)) {
            return $this->errorResponse('Access denied. You are not assigned to this project team.', null, 403);
        }

        $tasks = $this->tasks->getForSupervisorTeam($projectTeam)->groupBy('status');

        return $this->successResponse([
            'tasks' => $this->groupTasksByStatus($tasks),
        ], 'Supervised project team tasks retrieved successfully.');
    }

    public function review(Task $task, array $data): JsonResponse
    {
        $supervisorId = auth()->id();

        if (! $this->authenticatedUserIsSupervisor()) {
            return $this->errorResponse('Only supervisors can review tasks.', null, 403);
        }

        $task->loadMissing('projectTeam');

        if (! $task->projectTeam || ! $this->supervisorIsAssignedToTeam($task->projectTeam, $supervisorId)) {
            return $this->errorResponse('Access denied. You are not assigned to this task project team.', null, 403);
        }

        $review = $this->reviews
            ->updateOrCreateForSupervisor($task, $supervisorId, $data['review'])
            ->load('supervisor');

        $task->load(self::TASK_RELATIONS);

        $this->broadcastTaskReviewed($task, $review);
        $this->notifyTaskReviewAdded($task);

        return $this->successResponse([
            'review' => $this->reviewPayload($review),
            'task' => new TaskResource($task),
            'task_summary' => [
                'id' => $task->id,
                'project_team_id' => $task->project_team_id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
            ],
        ], 'Task review saved successfully.');
    }

    private function authenticatedUserIsSupervisor(): bool
    {
        return auth()->user()?->hasRole('Supervisor') ?? false;
    }

    private function supervisorIsAssignedToTeam(ProjectTeam $projectTeam, ?int $supervisorId): bool
    {
        if ($supervisorId === null) {
            return false;
        }

        return ProjectProposal::query()
            ->where('project_team_id', $projectTeam->id)
            ->where('supervisor_id', $supervisorId)
            ->exists();
    }

    private function groupTasksByStatus(Collection $tasks): array
    {
        $grouped = [];

        foreach (self::STATUSES as $status) {
            $grouped[$status] = TaskResource::collection($tasks->get($status, collect()));
        }

        return $grouped;
    }

    private function notifyTaskReviewAdded(Task $task): void
    {
        $task->loadMissing('assignedUser');

        if (! $task->assignedUser || $task->assigned_to === auth()->id()) {
            return;
        }

        $this->notifications->sendToUser($task->assignedUser, 'Task review added', "A supervisor added a review to your task: {$task->title}.", [
            'type' => 'task_review_added',
            'entity_type' => 'task',
            'entity_id' => $task->id,
        ]);
    }
    private function broadcastTaskReviewed(Task $task, TaskReview $review): void
    {
        try {
            TaskChanged::dispatch(
                $task->project_team_id,
                'task_reviewed',
                $task->id,
                $task,
                review: $this->reviewPayload($review),
            );
        } catch (Throwable $e) {
            Log::warning('Task broadcast failed', [
                'message' => $e->getMessage(),
                'action' => 'task_reviewed',
                'task_id' => $task->id,
                'project_team_id' => $task->project_team_id,
            ]);
        }
    }

    private function reviewPayload(TaskReview $review): array
    {
        return [
            'id' => $review->id,
            'review' => $review->review,
            'reviewed_at' => $review->reviewed_at,
            'supervisor' => $review->relationLoaded('supervisor') && $review->supervisor ? [
                'id' => $review->supervisor->id,
                'name' => $review->supervisor->name,
                'email' => $review->supervisor->email,
            ] : null,
        ];
    }
}
