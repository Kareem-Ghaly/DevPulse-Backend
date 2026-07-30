<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FinalSubmissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'project_team_id' => $this->project_team_id,
            'team' => $this->whenLoaded('team', fn() => [
                'id' => $this->team->id,
                'project_idea' => ['title' => $this->team->projectIdea?->title],
            ]),
            'proposal_drive_link' => $this->proposal_drive_link,
            'presentation_drive_link' => $this->presentation_drive_link,
            'code_drive_link' => $this->code_drive_link,
            'student_notes' => $this->student_notes,
            'status' => $this->status,
            'proposal_grade' => $this->proposal_grade,
            'proposal_feedback' => $this->proposal_feedback,
            'presentation_grade' => $this->presentation_grade,
            'presentation_feedback' => $this->presentation_feedback,
            'code_grade' => $this->code_grade,
            'code_feedback' => $this->code_feedback,
            'total_grade' => $this->total_grade,
            'graded_by' => $this->whenLoaded('grader', fn() => $this->grader->name),
            'graded_at' => $this->graded_at,
            'created_at' => $this->created_at,
        ];
    }
}