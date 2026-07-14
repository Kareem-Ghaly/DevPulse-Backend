<?php

namespace App\Interfaces;

use App\Models\ProjectTeam;
use App\Models\Task;
use Illuminate\Support\Collection;

interface TaskRepositoryInterface
{
    public function getForTeam(ProjectTeam $projectTeam): Collection;

    public function getForSupervisorTeam(ProjectTeam $projectTeam): Collection;

    public function create(array $data): Task;

    public function update(Task $task, array $data): Task;

    public function delete(Task $task): bool;
}
