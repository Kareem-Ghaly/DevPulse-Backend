<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface TaskRepositoryInterface
{
    public function getByProject(int $projectId): Collection;
}
