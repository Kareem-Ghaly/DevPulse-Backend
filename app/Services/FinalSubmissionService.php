<?php
namespace App\Services;

use App\Http\Resources\FinalSubmissionResource;
use App\Models\FinalSubmission;
use App\Models\ProjectTeam;
use App\Models\User;
use App\Notifications\DatabaseFirebaseNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class FinalSubmissionService
{
    use ApiResponse;

    public function store(array $data): JsonResponse
    {
        $team = ProjectTeam::find($data['project_team_id']);
        
        if (!$team || $team->leader_id !== auth()->id()) {
            return $this->errorResponse('Only team leader can submit.', null, 403);
        }

        $existing = FinalSubmission::where('project_team_id', $team->id)->first();
        
        if ($existing) {
            $existing->update($data);
            $submission = $existing->fresh();
        } else {
            $submission = FinalSubmission::create($data + ['status' => 'submitted']);
        }

        User::whereHas('roles', fn($q) => $q->where('name', 'CommitteeMember'))->chunk(50, function ($members) use ($team, $submission) {
            foreach ($members as $member) {
                $member->notify(new DatabaseFirebaseNotification(
                    title: 'Final Submission Received',
                    body: "Team: {$team->projectIdea?->title}",
                    data: [
                        'type' => 'final_submission',
                        'action_url' => '/committee/final-submissions',
                        'entity_type' => 'final_submission',
                        'entity_id' => $submission->id,
                    ]
                ));
            }
        });

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

        $leader = $finalSubmission->team->leader;
        if ($leader) {
            $leader->notify(new DatabaseFirebaseNotification(
                title: 'Final Submission Graded',
                body: "Total: {$finalSubmission->fresh()->total_grade}/300",
                data: [
                    'type' => 'final_submission_graded',
                    'action_url' => "/student/project-work-space/{$finalSubmission->team->project_idea_id}/final-submission",
                ]
            ));
        }

        return $this->successResponse(
            new FinalSubmissionResource($finalSubmission->fresh()),
            'Graded successfully.'
        );
    }
}