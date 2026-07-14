<?php

namespace App\Interfaces;

use App\Models\ProjectTeam;
use Illuminate\Support\Collection;

interface ProjectTeamRepositoryInterface
{
    public function findByProjectIdea(int $projectIdeaId): ?ProjectTeam;

    public function findById(int $id): ?ProjectTeam;

    public function findForUser(int $userId): ?ProjectTeam;

    public function getForUser(int $userId): Collection;

    public function create(array $data): ProjectTeam;

    public function markCompleted(ProjectTeam $team): ProjectTeam;

    public function countMembers(ProjectTeam $team): int;
}