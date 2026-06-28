<?php

namespace App\Interfaces;

use App\Models\ProjectIdea;

interface ProjectIdeaRepositoryInterface
{
    public function all();

    public function create(array $data): ProjectIdea;

    public function update(ProjectIdea $projectIdea, array $data): ProjectIdea;

    public function delete(ProjectIdea $projectIdea): bool;
}
