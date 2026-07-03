<?php

namespace App\Repositories;

use App\Interfaces\ProjectIdeaMatchRepositoryInterface;
use App\Models\ProjectIdeaMatch;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class ProjectIdeaMatchRepository implements ProjectIdeaMatchRepositoryInterface
{
    public function deleteByProjectIdea(int $projectIdeaId): int
    {
        return ProjectIdeaMatch::query()
            ->where('project_idea_id', $projectIdeaId)
            ->delete();
    }

    public function create(array $data): ProjectIdeaMatch
    {
        return ProjectIdeaMatch::query()->create($data);
    }

    public function getByProjectIdea(int $projectIdeaId): Collection
    {
        return ProjectIdeaMatch::query()
            ->with('student.studentProfile')
            ->where('project_idea_id', $projectIdeaId)
            ->orderByDesc('match_score')
            ->get();
    }

    public function existsForStudent(int $projectIdeaId, int $studentId): bool
    {
        return ProjectIdeaMatch::query()
            ->where('project_idea_id', $projectIdeaId)
            ->where('student_id', $studentId)
            ->exists();
    }

    public function getMatchableStudentProfiles(int $ownerId, array $excludedUserIds = []): Collection
    {
        return StudentProfile::query()
            ->with('user')
            ->where('user_id', '!=', $ownerId)
            ->when($excludedUserIds !== [], fn ($query): mixed => $query->whereNotIn('user_id', $excludedUserIds))
            ->whereHas('user', fn ($query) => $query->role('Student'))
            ->get();
    }

    public function getMatchableSupervisorProfiles(string $department = null): Collection
    {
        return \App\Models\SupervisorProfile::query()
            ->with('user')
            ->when($department, fn($query) => $query->where('department', $department))
            ->get();
    }
}
