<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Resources\ProjectIdeaResource;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Models\ProjectIdea;
use Illuminate\Http\JsonResponse;

class ProjectIdeaService extends BaseService
{
    public function __construct(private readonly ProjectIdeaRepositoryInterface $projectIdeas) {}

    public function index(): JsonResponse
    {
        $ideas = $this->projectIdeas->getPublishedIdeas([
            'owner_id' => auth()->id(),
        ]);

        return $this->paginatedResponse(ProjectIdeaResource::collection($ideas), $ideas);
    }

    public function store(array $data): JsonResponse
    {
        if (! auth()->user()?->hasRole(UserRole::Student->value)) {
            return $this->errorResponse('Only students can create project ideas.', null, 403);
        }

        $projectIdea = $this->projectIdeas->create([
            ...$data,
            'owner_id' => auth()->id(),
        ]);

        return $this->successResponse([
            'project_idea' => new ProjectIdeaResource($projectIdea),
        ], 'Project idea created successfully', 201);
    }

    public function show(int $projectIdeaId): JsonResponse
    {
        $projectIdea = $this->projectIdeas->findById($projectIdeaId);

        if (! $projectIdea) {
            return $this->errorResponse('Project idea not found.', null, 404);
        }

        if (! $this->canView($projectIdea)) {
            return $this->errorResponse('You are not allowed to view this project idea.', null, 403);
        }

        return $this->successResponse([
            'project_idea' => new ProjectIdeaResource($projectIdea),
        ]);
    }

    public function update(int $projectIdeaId, array $data): JsonResponse
    {
        $projectIdea = $this->projectIdeas->findOwnedByUser($projectIdeaId, auth()->id());

        if (! $projectIdea) {
            return $this->errorResponse('Only the project owner can update this idea.', null, 403);
        }

        $projectIdea = $this->projectIdeas->update($projectIdea, $data);

        return $this->successResponse([
            'project_idea' => new ProjectIdeaResource($projectIdea),
        ], 'Project idea updated successfully');
    }

    public function destroy(int $projectIdeaId): JsonResponse
    {
        $projectIdea = $this->projectIdeas->findOwnedByUser($projectIdeaId, auth()->id());

        if (! $projectIdea) {
            return $this->errorResponse('Only the project owner can delete this idea.', null, 403);
        }

        $this->projectIdeas->delete($projectIdea);

        return $this->successResponse(null, 'Project idea deleted successfully');
    }

    public function publish(int $projectIdeaId): JsonResponse
    {
        $projectIdea = $this->projectIdeas->findOwnedByUser($projectIdeaId, auth()->id());

        if (! $projectIdea) {
            return $this->errorResponse('Only the project owner can publish this idea.', null, 403);
        }

        if (empty($projectIdea->required_skills)) {
            return $this->errorResponse('A project cannot be published unless it has required skills.', [
                'required_skills' => ['Required skills are needed before publishing.'],
            ], 422);
        }

        $projectIdea = $this->projectIdeas->publish($projectIdea);

        return $this->successResponse([
            'project_idea' => new ProjectIdeaResource($projectIdea),
        ], 'Project idea published successfully');
    }

    private function isOwner(ProjectIdea $projectIdea): bool
    {
        return $projectIdea->owner_id === auth()->id();
    }

    private function canView(ProjectIdea $projectIdea): bool
    {
        return $this->isOwner($projectIdea)
            || ($projectIdea->is_public && in_array($projectIdea->status, ['published', 'team_completed'], true));
    }
}
