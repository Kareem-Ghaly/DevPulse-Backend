<?php

namespace App\Repositories;

use App\Interfaces\TaskAttachmentRepositoryInterface;
use App\Models\TaskAttachment;

class TaskAttachmentRepository implements TaskAttachmentRepositoryInterface
{
    public function create(array $data): TaskAttachment
    {
        return TaskAttachment::query()->create($data);
    }

    public function delete(TaskAttachment $attachment): bool
    {
        return $attachment->delete();
    }
}
