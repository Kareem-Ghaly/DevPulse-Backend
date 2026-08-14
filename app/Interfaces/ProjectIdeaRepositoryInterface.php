<?php

namespace App\Interfaces;

use App\Models\ProjectIdea;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProjectIdeaRepositoryInterface
{
    public function all();

    public function create(array $data): ProjectIdea;

    public function update(ProjectIdea $projectIdea, array $data): ProjectIdea;

    public function delete(ProjectIdea $projectIdea): bool;

    public function findById(int $id): ?ProjectIdea;

    public function findOwnedByUser(int $ideaId, int $ownerId): ?ProjectIdea;

    public function getPublishedIdeas(array $filters = []): LengthAwarePaginator;

    public function publish(ProjectIdea $idea): ProjectIdea;

    public function markTeamCompleted(ProjectIdea $idea): ProjectIdea;

    public function getByOwnerId(int $ownerId): Collection;
}
