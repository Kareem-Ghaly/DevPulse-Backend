<?php

namespace App\Interfaces;

use App\Models\ProjectTeam;

interface ProjectTeamRepositoryInterface
{
    public function findByProjectIdea(int $projectIdeaId): ?ProjectTeam;

    public function create(array $data): ProjectTeam;

    public function markCompleted(ProjectTeam $team): ProjectTeam;

    public function countMembers(ProjectTeam $team): int;
}
