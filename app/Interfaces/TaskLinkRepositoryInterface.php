<?php

namespace App\Interfaces;

use App\Models\TaskLink;

interface TaskLinkRepositoryInterface
{
    public function create(array $data): TaskLink;

    public function delete(TaskLink $link): bool;
}
