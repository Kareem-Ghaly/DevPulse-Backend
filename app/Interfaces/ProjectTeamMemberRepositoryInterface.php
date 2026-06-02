<?php

namespace App\Interfaces;

use App\Models\ProjectTeamMember;
use Illuminate\Support\Collection;

interface ProjectTeamMemberRepositoryInterface
{
    public function add(array $data): ProjectTeamMember;

    public function exists(int $teamId, int $studentId): bool;

    public function getByTeam(int $teamId): Collection;
}
