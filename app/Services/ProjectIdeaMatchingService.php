<?php

namespace App\Services;

use App\Interfaces\ProjectIdeaMatchRepositoryInterface;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Interfaces\ProjectTeamMemberRepositoryInterface;
use App\Interfaces\ProjectTeamRepositoryInterface;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectIdeaMatchingService extends BaseService
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $projectIdeas,
        private readonly ProjectIdeaMatchRepositoryInterface $matchRepository,
        private readonly ProjectTeamRepositoryInterface $teams,
        private readonly ProjectTeamMemberRepositoryInterface $teamMembers,
    ) {}

    public function students(int $projectIdeaId): JsonResponse
    {
        $projectIdea = $this->projectIdeas->findOwnedByUser($projectIdeaId, auth()->id());

        if (! $projectIdea) {
            return $this->errorResponse('Only the project owner can view student matches.', null, 403);
        }

        $requiredSkills = $this->trimSkills($projectIdea->required_skills ?? []);

        if ($requiredSkills === []) {
            return $this->errorResponse('Required skills are needed before generating matches.', [
                'required_skills' => ['Required skills are needed for smart student matching.'],
            ], 422);
        }

        $excludedUserIds = [];
        $team = $this->teams->findByProjectIdea($projectIdea->id);

        if ($team) {
            $excludedUserIds = $this->teamMembers->getByTeam($team->id)
                ->pluck('user_id')
                ->all();
        }

        $matches = $this->matchRepository->getMatchableStudentProfiles($projectIdea->owner_id, $excludedUserIds)
            ->map(function (StudentProfile $profile) use ($requiredSkills): array {
                $studentSkills = $this->normalizeSkills($profile->skills ?? []);
                $matched = [];
                $missing = [];

                foreach ($requiredSkills as $skill) {
                    if (in_array(mb_strtolower($skill), $studentSkills, true)) {
                        $matched[] = $skill;
                    } else {
                        $missing[] = $skill;
                    }
                }

                $percentage = round((count($matched) / count($requiredSkills)) * 100);

                return [
                    'student' => [
                        'id' => $profile->user->id,
                        'name' => $profile->user->name,
                        'email' => $profile->user->email,
                    ],
                    'matched_skills' => $matched,
                    'missing_skills' => $missing,
                    'match_percentage' => $percentage,
                ];
            })
            ->sortByDesc('match_percentage')
            ->values();

        return $this->successResponse([
            'matches' => $matches,
        ], 'Student matches retrieved successfully');
    }

    public function supervisors(int $projectIdeaId): JsonResponse
    {
        $projectIdea = $this->projectIdeas->findById($projectIdeaId);

        if (! $projectIdea) {
            return $this->errorResponse('Project idea not found.', null, 404);
        }

        if ((int) $projectIdea->owner_id !== (int) auth()->id()) {
            return $this->errorResponse('Only the project owner can view supervisor matches.', null, 403);
        }

        $studentProfile = $projectIdea->owner?->studentProfile;

        if (! $studentProfile) {
            return $this->errorResponse('Student profile is required before matching supervisors.', null, 422);
        }

        $studentSkills = $this->trimSkills($studentProfile->skills ?? []);
        $projectRequiredSkills = $this->trimSkills($projectIdea->required_skills ?? []);
        $targetSkills = $this->mergeSkills($studentSkills, $projectRequiredSkills);

        if ($targetSkills === []) {
            return $this->errorResponse('Student skills or required skills are needed before matching supervisors.', [
                'skills' => ['At least one student skill or project required skill is needed for supervisor matching.'],
            ], 422);
        }

        $studentDepartment = $studentProfile->department;
        $studentDepartmentKey = $this->normalizeText($studentDepartment);

        $supervisors = $this->matchRepository->getMatchableSupervisorProfiles($studentDepartmentKey)
            ->map(function (SupervisorProfile $profile) use ($studentDepartment, $studentDepartmentKey, $studentSkills, $projectRequiredSkills, $targetSkills): ?array {
                $supervisorDepartmentKey = $this->normalizeText($profile->department);

                if ($studentDepartmentKey === null || $supervisorDepartmentKey !== $studentDepartmentKey) {
                    return null;
                }

                $researchInterests = $this->trimSkills($profile->research_interests ?? []);

                if ($researchInterests === []) {
                    return null;
                }

                $researchInterestKeys = $this->normalizeSkills($researchInterests);
                $matched = [];
                $missing = [];

                foreach ($targetSkills as $skill) {
                    $normalizedSkill = $this->normalizeText($skill);

                    if ($normalizedSkill !== null && in_array($normalizedSkill, $researchInterestKeys, true)) {
                        $matched[] = $skill;
                    } else {
                        $missing[] = $skill;
                    }
                }

                $percentage = round((count($matched) / count($targetSkills)) * 100);

                if ($percentage <= 0) {
                    return null;
                }

                return [
                    'supervisor' => [
                        'id' => $profile->user->id,
                        'name' => $profile->full_name ?? $profile->user->name,
                        'email' => $profile->user->email,
                        'academic_title' => $profile->academic_title,
                        'department' => $profile->department,
                        'specialization' => $profile->specialization,
                    ],
                    'department_match' => true,
                    'student_department' => $studentDepartment,
                    'supervisor_department' => $profile->department,
                    'student_skills' => $studentSkills,
                    'project_required_skills' => $projectRequiredSkills,
                    'target_skills' => $targetSkills,
                    'supervisor_research_interests' => $researchInterests,
                    'matched_skills' => $matched,
                    'missing_skills' => $missing,
                    'match_percentage' => $percentage,
                ];
            })
            ->filter()
            ->sortByDesc('match_percentage')
            ->values();

        $perPage = min(100, max(1, (int) request()->query('per_page', 10)));
        $currentPage = max(1, LengthAwarePaginator::resolveCurrentPage());
        $currentItems = $supervisors->forPage($currentPage, $perPage)->values()->all();

        $paginated = new LengthAwarePaginator(
            $currentItems,
            $supervisors->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return $this->successResponse([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 'Supervisor matches retrieved successfully');
    }

    private function normalizeSkills(array $skills): array
    {
        return collect($skills)
            ->filter(fn ($skill): bool => is_string($skill) && trim($skill) !== '')
            ->map(fn (string $skill): string => mb_strtolower(trim($skill)))
            ->unique()
            ->values()
            ->all();
    }

    private function trimSkills(array $skills): array
    {
        return collect($skills)
            ->filter(fn ($skill): bool => is_string($skill) && trim($skill) !== '')
            ->map(fn (string $skill): string => trim($skill))
            ->unique(fn (string $skill): string => mb_strtolower($skill))
            ->values()
            ->all();
    }

    private function mergeSkills(array $studentSkills, array $projectRequiredSkills): array
    {
        return collect([...$studentSkills, ...$projectRequiredSkills])
            ->unique(fn (string $skill): string => mb_strtolower($skill))
            ->values()
            ->all();
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return mb_strtolower(trim($value));
    }
}
