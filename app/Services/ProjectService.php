<?php

namespace App\Services;

use App\Http\Resources\ProjectResource;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Interfaces\ProjectRepositoryInterface;
use App\Interfaces\ProposalRepositoryInterface;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProjectService extends BaseService
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $projectIdeas,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProposalRepositoryInterface $proposals,
    ) {}

    public function createFromIdea(int $projectIdeaId): JsonResponse
    {
        $idea = $this->projectIdeas->findById($projectIdeaId);

        if (! $idea) {
            return $this->errorResponse('Project idea not found.', null, 404);
        }

        if ($idea->owner_id !== auth()->id()) {
            return $this->errorResponse('Only project owner can create project.', null, 403);
        }

        if (! $idea->team || $idea->team->status !== 'completed') {
            return $this->errorResponse('Team must be completed before creating project.', null, 422);
        }

        $existingProject = $this->projects->findByProjectIdea($idea->id);

        if ($existingProject) {
            $existingProject = $this->projects->loadDetails($existingProject);

            return $this->successResponse([
                'project_id' => $existingProject->id,
                'project_idea_id' => $existingProject->project_idea_id,
                'project_team_id' => $existingProject->project_team_id,
                'user_ids' => $existingProject->team->members->pluck('user_id')->values(),
                'proposal_id' => $existingProject->proposal?->id,
                'project' => new ProjectResource($existingProject),
            ], 'Project already exists');
        }

        $project = DB::transaction(function () use ($idea): Project {
            $project = $this->projects->create([
                'project_idea_id' => $idea->id,
                'project_team_id' => $idea->team->id,
                'owner_id' => $idea->owner_id,
                'title' => $idea->title,
                'description' => $idea->description,
                'status' => 'active',
            ]);

            $this->proposals->firstOrCreate(
                ['project_id' => $project->id],
                [
                    'content' => [
                        'title' => $idea->title,
                        'abstract' => $idea->abstract,
                        'description' => $idea->description,
                        'tech_stack' => $idea->tech_stack,
                        'required_skills' => $idea->required_skills,
                        'needed_roles' => $idea->needed_roles,
                        'domain' => $idea->domain,
                    ],
                    'status' => 'draft',
                ]
            );

            return $this->projects->loadDetails($project);
        });

        return $this->successResponse([
            'project_id' => $project->id,
            'project_idea_id' => $project->project_idea_id,
            'project_team_id' => $project->project_team_id,
            'user_ids' => $project->team->members->pluck('user_id')->values(),
            'proposal_id' => $project->proposal?->id,
            'project' => new ProjectResource($project),
        ], 'Project created successfully', 201);
    }

    public function show(int $projectId): JsonResponse
    {
        $project = $this->projects->findById($projectId);

        if (! $project) {
            return $this->errorResponse('Project not found.', null, 404);
        }

        return $this->successResponse([
            'project' => new ProjectResource($project),
        ], 'Project retrieved successfully');
    }
}
