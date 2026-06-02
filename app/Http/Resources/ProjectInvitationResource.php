<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_idea_id' => $this->project_idea_id,
            'project_idea' => new ProjectIdeaResource($this->whenLoaded('projectIdea')),
            'sender_id' => $this->sender_id,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'receiver_id' => $this->receiver_id,
            'receiver' => new UserResource($this->whenLoaded('receiver')),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
