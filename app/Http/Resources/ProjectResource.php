<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_idea_id' => $this->project_idea_id,
            'project_team_id' => $this->project_team_id,
            'owner_id' => $this->owner_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,

            'users' => $this->whenLoaded('team', function () {
                return $this->team->members->map(fn ($member) => [
                    'id' => $member->user->id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                    'role_in_team' => $member->role,
                ])->values();
            }),

            'proposal' => new ProposalResource($this->whenLoaded('proposal')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
