<?php

namespace App\Services;

use App\Interfaces\ProjectIdeaMatchRepositoryInterface;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Interfaces\ProjectTeamMemberRepositoryInterface;
use App\Interfaces\ProjectTeamRepositoryInterface;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;

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

    public function supervisors(int $projectIdeaId): JsonResponse
{
    $projectIdea = $this->projectIdeas->findById($projectIdeaId);

    if (! $projectIdea) {
        return $this->errorResponse('Project idea not found.', null, 404);
    }

    $requiredSkills = $this->trimSkills($projectIdea->required_skills ?? []);

    if ($requiredSkills === []) {
        return $this->errorResponse('Required skills are needed before matching supervisors.', null, 422);
    }

    $supervisors = $this->matchRepository->getMatchableSupervisorProfiles()
        ->map(function (\App\Models\SupervisorProfile $profile) use ($requiredSkills): array {
            $interests = $this->normalizeSkills($profile->research_interests ?? []);
            $matched = [];
            $missing = [];

            foreach ($requiredSkills as $skill) {
                if (in_array(mb_strtolower($skill), $interests, true)) {
                    $matched[] = $skill;
                } else {
                    $missing[] = $skill;
                }
            }

            $percentage = round((count($matched) / count($requiredSkills)) * 100);

            return [
                'supervisor' => [
                    'id' => $profile->user->id,
                    'name' => $profile->full_name ?? $profile->user->name,
                    'email' => $profile->user->email,
                    'academic_title' => $profile->academic_title,
                    'specialization' => $profile->specialization,
                ],
                'matched_interests' => $matched,
                'missing_interests' => $missing,
                'match_percentage' => $percentage,
            ];
        })
        ->filter(fn($item) => $item['match_percentage'] > 0) 
        
        ->sortByDesc('match_percentage')
        ->values();



    $perPage = request()->get('per_page', 10);
    $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
    $currentItems = $supervisors->slice(($currentPage - 1) * $perPage, $perPage)->all();

    $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
        $currentItems,
        $supervisors->count(),
        $perPage,
        $currentPage,
        ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
    );

    return $this->successResponse([
        'data' => $paginated->items(),
        'meta' => [
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]
    ], 'Supervisor matches retrieved successfully');
}
}
