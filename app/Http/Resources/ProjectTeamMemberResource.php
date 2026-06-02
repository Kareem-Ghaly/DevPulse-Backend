<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectTeamMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_team_id' => $this->project_team_id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'role' => $this->role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
