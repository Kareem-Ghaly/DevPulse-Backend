<?php

namespace App\Services;

use App\Events\TaskChanged;
use App\Http\Resources\TaskAttachmentResource;
use App\Http\Resources\TaskLinkResource;
use App\Http\Resources\TaskResource;
use App\Interfaces\ProjectTeamMemberRepositoryInterface;
use App\Interfaces\TaskAttachmentRepositoryInterface;
use App\Interfaces\TaskLinkRepositoryInterface;
use App\Interfaces\TaskRepositoryInterface;
use App\Models\ProjectProposal;
use App\Models\ProjectTeam;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskLink;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TaskService
{
    use ApiResponse;

    private const STATUSES = ['backlog', 'todo', 'in_progress', 'done'];

    private const TASK_RELATIONS = ['assignedUser', 'creator', 'completedBy', 'attachments.uploader', 'links.creator'];

    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskAttachmentRepositoryInterface $attachments,
        private readonly TaskLinkRepositoryInterface $links,
        private readonly ProjectTeamMemberRepositoryInterface $teamMembers,
        private readonly NotificationService $notifications,
    ) {}

    public function members(ProjectTeam $projectTeam): JsonResponse
    {
        $members = $this->teamMembers->getByTeam($projectTeam->id)
            ->map(fn ($member): array => [
                'id' => $member->user?->id,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
            ])
            ->filter(fn (array $member): bool => $member['id'] !== null)
            ->values();

        return $this->successResponse([
            'members' => $members,
        ], 'Project team members retrieved successfully.');
    }

    public function index(ProjectTeam $projectTeam): JsonResponse
    {
        $tasks = $this->tasks->getForTeam($projectTeam)->groupBy('status');

        return $this->successResponse([
            'tasks' => $this->groupTasksByStatus($tasks),
        ], 'Project team tasks retrieved successfully.');
    }

    public function store(ProjectTeam $projectTeam, array $data): JsonResponse
    {
        if (! $this->assignedUserIsValid($projectTeam->id, $data)) {
            return $this->invalidAssignedUserResponse();
        }

        $task = $this->tasks->create([
            ...$data,
            'project_team_id' => $projectTeam->id,
            'created_by' => auth()->id(),
            'status' => $data['status'] ?? 'backlog',
            'priority' => $data['priority'] ?? 'medium',
            'last_update' => now(),
            'completed_at' => ($data['status'] ?? 'backlog') === 'done' ? now() : null,
        ]);

        $task->load(self::TASK_RELATIONS);

        $this->broadcastTaskChange('task_created', $projectTeam->id, $task->id, $task);
        $this->notifyTaskAssigned($task);

        return $this->successResponse([
            'task' => new TaskResource($task),
        ], 'Task created successfully.', 201);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load(['projectTeam', ...self::TASK_RELATIONS]);

        return $this->successResponse([
            'task' => new TaskResource($task),
        ], 'Task retrieved successfully.');
    }

    public function update(Task $task, array $data): JsonResponse
    {
        if (! $this->assignedUserIsValid($task->project_team_id, $data)) {
            return $this->invalidAssignedUserResponse();
        }

        $previousAssignedTo = $task->assigned_to;

        if (array_key_exists('status', $data)) {
            $data['completed_at'] = $data['status'] === 'done' ? now() : null;
        }

        $data['last_update'] = now();

        $task = $this->tasks->update($task, $data);

        $this->broadcastTaskChange('task_updated', $task->project_team_id, $task->id, $task);

        if (array_key_exists('assigned_to', $data) && (int) $previousAssignedTo !== (int) $task->assigned_to) {
            $this->notifyTaskAssigned($task);
        }

        return $this->successResponse([
            'task' => new TaskResource($task),
        ], 'Task updated successfully.');
    }

    public function updateStatus(Task $task, array $data): JsonResponse
    {
        $createdAttachments = $this->storeTaskFiles($task, $data['files'] ?? []);
        $createdLink = $this->storeTaskLinkWhenProvided($task, $data);

        $updateData = [
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'done' ? now() : null,
            'last_update' => now(),
        ];

        if ($data['status'] === 'done') {
            if (Schema::hasColumn('tasks', 'completed_by')) {
                $updateData['completed_by'] = auth()->id();
            }

            if (Schema::hasColumn('tasks', 'completion_notes')) {
                $updateData['completion_notes'] = $data['notes'] ?? null;
            }
        }

        $task = $this->tasks->update($task, $updateData);
        $task->load(self::TASK_RELATIONS);

        $action = $data['status'] === 'done' ? 'task_completed' : 'task_status_changed';

        $this->broadcastTaskChange(
            $action,
            $task->project_team_id,
            $task->id,
            $task,
            link: $createdLink ? $this->linkPayload($createdLink) : null,
            attachments: $createdAttachments->isNotEmpty() ? $this->attachmentsPayload($createdAttachments) : null,
        );

        $this->notifyTaskStatusChanged($task);

        return $this->successResponse([
            'task' => new TaskResource($task),
            'attachments' => TaskAttachmentResource::collection($createdAttachments),
            'link' => $createdLink ? new TaskLinkResource($createdLink) : null,
        ], 'Task status updated successfully.');
    }

    public function destroy(Task $task): JsonResponse
    {
        $projectTeamId = $task->project_team_id;
        $taskId = $task->id;

        $this->broadcastTaskChange('task_deleted', $projectTeamId, $taskId, $task);

        $this->tasks->delete($task);

        return $this->successResponse(null, 'Task deleted successfully.');
    }

    public function addAttachments(Task $task, array $data): JsonResponse
    {
        $created = $this->storeTaskFiles($task, $data['files']);

        $task = $this->updateTaskStatusWhenProvided($task, $data);
        $task->load(self::TASK_RELATIONS);

        $this->broadcastTaskChange('attachment_added', $task->project_team_id, $task->id, $task);

        return $this->successResponse([
            'attachments' => TaskAttachmentResource::collection($created),
            'task' => new TaskResource($task),
        ], 'Task attachments added successfully.', 201);
    }

    public function deleteAttachment(TaskAttachment $attachment): JsonResponse
    {
        $attachment->load('task');
        $taskId = $attachment->task_id;
        $projectTeamId = $attachment->task->project_team_id;
        $payload = [
            'id' => $attachment->id,
            'file_name' => $attachment->file_name,
            'file_path' => $attachment->file_path,
        ];

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $this->attachments->delete($attachment);

        $this->broadcastTaskChange('attachment_deleted', $projectTeamId, $taskId, attachment: $payload);

        return $this->successResponse(null, 'Task attachment deleted successfully.');
    }

    public function addLink(Task $task, array $data): JsonResponse
    {
        $link = $this->links->create([
            'title' => $data['title'] ?? null,
            'url' => $data['url'],
            'task_id' => $task->id,
            'created_by' => auth()->id(),
        ])->load('creator');

        $task = $this->updateTaskStatusWhenProvided($task, $data);
        $task->load(self::TASK_RELATIONS);

        $this->broadcastTaskChange('link_added', $task->project_team_id, $task->id, $task, link: $this->linkPayload($link));

        return $this->successResponse([
            'link' => new TaskLinkResource($link),
            'task' => new TaskResource($task),
        ], 'Task link added successfully.', 201);
    }

    public function deleteLink(TaskLink $link): JsonResponse
    {
        $link->load('task');
        $taskId = $link->task_id;
        $projectTeamId = $link->task->project_team_id;
        $payload = $this->linkPayload($link);

        $this->links->delete($link);

        $this->broadcastTaskChange('link_deleted', $projectTeamId, $taskId, link: $payload);

        return $this->successResponse(null, 'Task link deleted successfully.');
    }

    private function storeTaskFiles(Task $task, array $files): Collection
    {
        return collect($files)->map(function ($file) use ($task): TaskAttachment {
            $path = $file->store('tasks/'.$task->id.'/attachments', 'public');

            return $this->attachments->create([
                'task_id' => $task->id,
                'uploaded_by' => auth()->id(),
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ])->load('uploader');
        });
    }

    private function storeTaskLinkWhenProvided(Task $task, array $data): ?TaskLink
    {
        if (empty($data['link_url'])) {
            return null;
        }

        return $this->links->create([
            'task_id' => $task->id,
            'created_by' => auth()->id(),
            'title' => $data['link_title'] ?? null,
            'url' => $data['link_url'],
        ])->load('creator');
    }

    private function updateTaskStatusWhenProvided(Task $task, array $data): Task
    {
        if (! array_key_exists('status', $data) || $data['status'] === null) {
            return $task;
        }

        return $this->tasks->update($task, [
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'done' ? now() : null,
            'last_update' => now(),
        ]);
    }

    private function assignedUserIsValid(int $projectTeamId, array $data): bool
    {
        if (! array_key_exists('assigned_to', $data) || $data['assigned_to'] === null) {
            return true;
        }

        return $this->teamMembers->exists($projectTeamId, (int) $data['assigned_to']);
    }

    private function invalidAssignedUserResponse(): JsonResponse
    {
        return $this->errorResponse(
            'The assigned user must be a member of this project team.',
            null,
            422
        );
    }

    private function groupTasksByStatus(Collection $tasks): array
    {
        $grouped = [];

        foreach (self::STATUSES as $status) {
            $grouped[$status] = TaskResource::collection($tasks->get($status, collect()));
        }

        return $grouped;
    }

    private function attachmentsPayload(Collection $attachments): array
    {
        return $attachments->map(fn (TaskAttachment $attachment): array => [
            'id' => $attachment->id,
            'file_name' => $attachment->file_name,
            'file_path' => $attachment->file_path,
            'file_type' => $attachment->file_type,
            'file_size' => $attachment->file_size,
        ])->values()->all();
    }

    private function linkPayload(TaskLink $link): array
    {
        return [
            'id' => $link->id,
            'title' => $link->title,
            'url' => $link->url,
        ];
    }

    private function notifyTaskAssigned(Task $task): void
    {
        $task->loadMissing('assignedUser');

        if (! $task->assignedUser || $task->assigned_to === auth()->id()) {
            return;
        }

        $this->notifications->sendToUser($task->assignedUser, 'Task assigned', "You were assigned a task: {$task->title}.", [
            'type' => 'task_assigned',
            'entity_type' => 'task',
            'entity_id' => $task->id,
        ]);
    }

    private function notifyTaskStatusChanged(Task $task): void
    {
        $task->loadMissing(['projectTeam.leader']);

        if (! $task->projectTeam) {
            return;
        }

        $users = collect();
        $proposal = ProjectProposal::query()
            ->with('supervisorUser')
            ->where('project_team_id', $task->project_team_id)
            ->whereNotNull('supervisor_id')
            ->first();

        if ($proposal?->supervisorUser) {
            $users->push($proposal->supervisorUser);
        }

        if ($task->projectTeam->leader) {
            $users->push($task->projectTeam->leader);
        }

        $actorId = auth()->id();
        $users = $users->filter(fn ($user): bool => $user && $user->id !== $actorId);

        $this->notifications->sendToUsers($users, 'Task status changed', "Task status changed: {$task->title}.", [
            'type' => 'task_status_changed',
            'entity_type' => 'task',
            'entity_id' => $task->id,
        ]);
    }
    private function broadcastTaskChange(
        string $action,
        int $projectTeamId,
        ?int $taskId = null,
        ?Task $task = null,
        ?array $attachment = null,
        ?array $link = null,
        ?array $attachments = null,
    ): void {
        try {
            TaskChanged::dispatch($projectTeamId, $action, $taskId, $task, $attachment, $link, $attachments);
        } catch (Throwable $e) {
            Log::warning('Task broadcast failed', [
                'message' => $e->getMessage(),
                'action' => $action,
                'task_id' => $taskId,
                'project_team_id' => $projectTeamId,
            ]);
        }
    }
}
