<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_team_id' => $this->project_team_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'assigned_to' => $this->assigned_to,
            'assigned_user' => $this->whenLoaded('assignedUser', fn () => [
                'id' => $this->assignedUser?->id,
                'name' => $this->assignedUser?->name,
                'email' => $this->assignedUser?->email,
            ]),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'email' => $this->creator?->email,
            ]),
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'completed_by' => $this->completed_by,
            'completed_by_user' => $this->whenLoaded('completedBy', fn () => [
                'id' => $this->completedBy?->id,
                'name' => $this->completedBy?->name,
                'email' => $this->completedBy?->email,
            ]),
            'completion_notes' => $this->completion_notes,
            'last_update' => $this->last_update,
            'attachments' => TaskAttachmentResource::collection($this->whenLoaded('attachments')),
            'links' => TaskLinkResource::collection($this->whenLoaded('links')),
            'latest_review' => $this->whenLoaded('latestReview', fn () => $this->latestReview ? [
                'id' => $this->latestReview->id,
                'review' => $this->latestReview->review,
                'reviewed_at' => $this->latestReview->reviewed_at,
                'supervisor' => $this->latestReview->relationLoaded('supervisor') && $this->latestReview->supervisor ? [
                    'id' => $this->latestReview->supervisor->id,
                    'name' => $this->latestReview->supervisor->name,
                    'email' => $this->latestReview->supervisor->email,
                ] : null,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
