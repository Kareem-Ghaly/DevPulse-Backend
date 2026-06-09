<?php

namespace App\Interfaces;

use App\Models\Project;

interface ProjectRepositoryInterface
{
    public function findById(int $id): ?Project;

    public function findByProjectIdea(int $projectIdeaId): ?Project;

    public function create(array $data): Project;

    public function loadDetails(Project $project): Project;
}
