<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Http\Resources\FinalSubmissionResource;
use App\Interfaces\UserRepositoryInterface;
use App\Models\FinalSubmission;
use App\Models\ProjectTeam;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class FinalSubmissionService
{
    use ApiResponse;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly NotificationService $notifications,
    ) {}

    public function store(array $data): JsonResponse
    {
        $team = ProjectTeam::with('projectIdea')->find($data['project_team_id']);

        if (! $team || $team->leader_id !== auth()->id()) {
            return $this->errorResponse('Only team leader can submit.', null, 403);
        }

        $existing = FinalSubmission::where('project_team_id', $team->id)->first();

        if ($existing) {
            $existing->update($data);
            $submission = $existing->fresh();
        } else {
            $submission = FinalSubmission::create($data + ['status' => 'submitted']);
        }

        $this->notifications->sendToUsers(
            $this->users->getActiveUsersByRole(UserRole::CommitteeMember->value),
            'Final Submission Received',
            "Team: {$team->projectIdea?->title}",
            [
                'type' => NotificationType::FinalSubmissionReceived->value,
                'entity_type' => 'final_submission',
                'entity_id' => $submission->id,
                'final_submission_id' => $submission->id,
                'team_id' => $team->id,
                'project_idea_id' => $team->project_idea_id,
                'action_url' => '/committee/final-submissions',
            ]
        );

        return $this->successResponse(
            new FinalSubmissionResource($submission->load('team.projectIdea')),
            'Final submission saved successfully.',
            201
        );
    }

    public function showByTeam(ProjectTeam $projectTeam): JsonResponse
    {
        $submission = FinalSubmission::where('project_team_id', $projectTeam->id)
            ->with(['team.projectIdea', 'grader'])
            ->first();

        return $this->successResponse(
            $submission ? new FinalSubmissionResource($submission) : null,
            'Submission retrieved.'
        );
    }

    public function showForStudent(): JsonResponse
    {
        $user = auth()->user();

        $team = ProjectTeam::where('leader_id', $user->id)
            ->orWhereHas('members', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (! $team) {
            return $this->errorResponse('You are not a member of any project team.', null, 404);
        }

        $submission = FinalSubmission::where('project_team_id', $team->id)
            ->with(['team.projectIdea', 'grader'])
            ->first();

        if (! $submission) {
            return $this->errorResponse('No final submission found for your team.', null, 404);
        }

        return $this->successResponse(
            new FinalSubmissionResource($submission),
            'Final submission retrieved successfully.'
        );
    }

    public function index(): JsonResponse
    {
        $submissions = FinalSubmission::with(['team.projectIdea', 'grader'])
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse(
            FinalSubmissionResource::collection($submissions),
            $submissions,
            'Submissions retrieved.'
        );
    }

    public function show(FinalSubmission $finalSubmission): JsonResponse
    {
        return $this->successResponse(
            new FinalSubmissionResource($finalSubmission->load(['team.projectIdea', 'grader'])),
            'Submission retrieved.'
        );
    }

    public function grade(FinalSubmission $finalSubmission, array $data): JsonResponse
    {
        $proposalGrade = $data['proposal_grade'] ?? $finalSubmission->proposal_grade ?? 0;
        $presentationGrade = $data['presentation_grade'] ?? $finalSubmission->presentation_grade ?? 0;
        $codeGrade = $data['code_grade'] ?? $finalSubmission->code_grade ?? 0;

        $finalSubmission->update([
            'proposal_grade' => $proposalGrade,
            'proposal_feedback' => $data['proposal_feedback'] ?? $finalSubmission->proposal_feedback,
            'presentation_grade' => $presentationGrade,
            'presentation_feedback' => $data['presentation_feedback'] ?? $finalSubmission->presentation_feedback,
            'code_grade' => $codeGrade,
            'code_feedback' => $data['code_feedback'] ?? $finalSubmission->code_feedback,
            'total_grade' => $proposalGrade + $presentationGrade + $codeGrade,
            'status' => 'graded',
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        $finalSubmission = $finalSubmission->fresh(['team.leader', 'team.projectIdea']);
        $leader = $finalSubmission->team?->leader;

        if ($leader && $leader->id !== auth()->id()) {
            $this->notifications->sendToUser($leader, 'Final Submission Graded', "Total: {$finalSubmission->total_grade}/300", [
                'type' => NotificationType::FinalSubmissionGraded->value,
                'entity_type' => 'final_submission',
                'entity_id' => $finalSubmission->id,
                'final_submission_id' => $finalSubmission->id,
                'team_id' => $finalSubmission->project_team_id,
                'project_idea_id' => $finalSubmission->team?->project_idea_id,
                'evaluation_id' => $finalSubmission->id,
                'status' => $finalSubmission->status,
                'action_url' => "/student/project-work-space/{$finalSubmission->team?->project_idea_id}/final-submission",
            ]);
        }

        return $this->successResponse(
            new FinalSubmissionResource($finalSubmission),
            'Graded successfully.'
        );
    }
}
