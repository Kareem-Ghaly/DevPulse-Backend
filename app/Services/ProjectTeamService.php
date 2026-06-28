<?php

namespace App\Services;

use App\Http\Resources\ProjectTeamResource;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Interfaces\ProjectTeamMemberRepositoryInterface;
use App\Interfaces\ProjectTeamRepositoryInterface;
use App\Models\ProjectIdea;
use App\Models\ProjectTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProjectTeamService extends BaseService
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $projectIdeas,
        private readonly ProjectTeamRepositoryInterface $teams,
        private readonly ProjectTeamMemberRepositoryInterface $teamMembers,
    ) {}

    public function show(int $projectIdeaId): JsonResponse
    {
        $projectIdea = $this->projectIdeas->findById($projectIdeaId);

        if (! $projectIdea) {
            return $this->errorResponse('Project idea not found.', null, 404);
        }

        $team = $this->teams->findByProjectIdea($projectIdea->id);

        if (! $team) {
            return $this->successResponse([
                'team' => null,
            ], 'Project team has not been created yet');
        }

        return $this->successResponse([
            'team' => new ProjectTeamResource($team),
        ], 'Project team retrieved successfully');
    }

    public function addAcceptedMember(ProjectIdea $projectIdea, int $receiverId): ProjectTeam
    {
        return DB::transaction(function () use ($projectIdea, $receiverId): ProjectTeam {
            $team = $this->teams->findByProjectIdea($projectIdea->id)
                ?? $this->teams->create([
                    'project_idea_id' => $projectIdea->id,
                    'leader_id' => $projectIdea->owner_id,
                    'status' => 'forming',
                ]);

            $this->teamMembers->add([
                'project_team_id' => $team->id,
                'user_id' => $projectIdea->owner_id,
                'role' => 'leader',
            ]);

            if ($this->teams->countMembers($team) >= $projectIdea->team_size) {
                return $this->markCompletedIfFull($team, $projectIdea);
            }

            $this->teamMembers->add([
                'project_team_id' => $team->id,
                'user_id' => $receiverId,
                'role' => 'member',
            ]);

            return $this->markCompletedIfFull($team, $projectIdea);
        });
    }

    public function isFull(ProjectIdea $projectIdea): bool
    {
        $team = $this->teams->findByProjectIdea($projectIdea->id);

        return $team && $this->teams->countMembers($team) >= $projectIdea->team_size;
    }

    private function markCompletedIfFull(ProjectTeam $team, ProjectIdea $projectIdea): ProjectTeam
    {
        if ($this->teams->countMembers($team) >= $projectIdea->team_size) {
            $team = $this->teams->markCompleted($team);
            $this->projectIdeas->markTeamCompleted($projectIdea);
        }

        return $this->teams->findByProjectIdea($projectIdea->id) ?? $team;
    }

    public function getStudentProjects(int $studentId): JsonResponse
    {
        $projects = $this->projectIdeas->getByOwnerId($studentId);

        if ($projects->isEmpty()) {
            return $this->successResponse([
                'projects' => [],
            ], 'You haven\'t created any project ideas yet.');
        }

        return $this->successResponse([
            'projects' => $projects, 
        ], 'Student project ideas retrieved successfully.');
    }
}
