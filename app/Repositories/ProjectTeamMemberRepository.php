<?php

namespace App\Repositories;

use App\Interfaces\ProjectTeamMemberRepositoryInterface;
use App\Models\ProjectTeamMember;
use Illuminate\Support\Collection;

class ProjectTeamMemberRepository implements ProjectTeamMemberRepositoryInterface
{
    public function add(array $data): ProjectTeamMember
    {
        return ProjectTeamMember::query()->firstOrCreate(
            [
                'project_team_id' => $data['project_team_id'],
                'user_id' => $data['user_id'],
            ],
            ['role' => $data['role'] ?? 'member']
        );
    }

    public function exists(int $teamId, int $studentId): bool
    {
        return ProjectTeamMember::query()
            ->where('project_team_id', $teamId)
            ->where('user_id', $studentId)
            ->exists();
    }

    public function getByTeam(int $teamId): Collection
    {
        return ProjectTeamMember::query()
            ->with('user')
            ->where('project_team_id', $teamId)
            ->get();
    }
}
