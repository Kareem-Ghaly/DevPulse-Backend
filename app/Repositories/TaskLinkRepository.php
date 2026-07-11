<?php

namespace App\Repositories;

use App\Interfaces\TaskLinkRepositoryInterface;
use App\Models\TaskLink;

class TaskLinkRepository implements TaskLinkRepositoryInterface
{
    public function create(array $data): TaskLink
    {
        return TaskLink::query()->create($data);
    }

    public function delete(TaskLink $link): bool
    {
        return $link->delete();
    }
}
