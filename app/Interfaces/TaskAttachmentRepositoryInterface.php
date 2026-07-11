<?php

namespace App\Interfaces;

use App\Models\TaskAttachment;

interface TaskAttachmentRepositoryInterface
{
    public function create(array $data): TaskAttachment;

    public function delete(TaskAttachment $attachment): bool;
}
