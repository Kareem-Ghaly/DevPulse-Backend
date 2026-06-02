<?php

namespace App\Interfaces;

use App\Models\ProjectIdeaMatch;
use Illuminate\Support\Collection;

interface ProjectIdeaMatchRepositoryInterface
{
    public function deleteByProjectIdea(int $projectIdeaId): int;

    public function create(array $data): ProjectIdeaMatch;

    public function getByProjectIdea(int $projectIdeaId): Collection;

    public function existsForStudent(int $projectIdeaId, int $studentId): bool;

    public function getMatchableStudentProfiles(int $ownerId, array $excludedUserIds = []): Collection;
}
