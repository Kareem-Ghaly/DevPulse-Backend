<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $projectTeamId,
        public string $action,
        public ?int $taskId = null,
        public ?Task $task = null,
        public ?array $attachment = null,
        public ?array $link = null,
        public ?array $attachments = null,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('project-team.'.$this->projectTeamId.'.tasks'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.changed';
    }

    public function broadcastWith(): array
    {
        return array_filter([
            'action' => $this->action,
            'project_team_id' => $this->projectTeamId,
            'task_id' => $this->taskId ?? $this->task?->id,
            'task' => $this->task ? [
                'id' => $this->task->id,
                'title' => $this->task->title,
                'status' => $this->task->status,
                'priority' => $this->task->priority,
                'completed_at' => $this->task->completed_at,
                'completed_by' => $this->task->completed_by,
                'completion_notes' => $this->task->completion_notes,
            ] : null,
            'attachment' => $this->attachment,
            'attachments' => $this->attachments,
            'link' => $this->link,
        ], fn ($value) => $value !== null);
    }
}
