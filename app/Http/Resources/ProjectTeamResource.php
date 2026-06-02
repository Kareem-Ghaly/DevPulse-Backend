<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectTeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_idea_id' => $this->project_idea_id,
            'project_idea' => new ProjectIdeaResource($this->whenLoaded('projectIdea')),
            'leader_id' => $this->leader_id,
            'leader' => new UserResource($this->whenLoaded('leader')),
            'status' => $this->status,
            'members' => ProjectTeamMemberResource::collection($this->whenLoaded('members')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
