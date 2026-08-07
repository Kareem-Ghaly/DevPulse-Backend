<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_at' => $this->scheduled_at,
            'duration_minutes' => $this->duration_minutes,
            'meeting_link' => $this->meeting_link,
            'status' => $this->status,
            'scheduler_role' => $this->scheduler_role,
            'team' => $this->whenLoaded('team', fn () => [
                'id' => $this->team->id,
                'project_idea' => ['title' => $this->team->projectIdea?->title],
            ]),
            'scheduler' => $this->whenLoaded('scheduler', fn () => [
                'id' => $this->scheduler->id,
                'name' => $this->scheduler->name,
            ]),
        ];
    }
}