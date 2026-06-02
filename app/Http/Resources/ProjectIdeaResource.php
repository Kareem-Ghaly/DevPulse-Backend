<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectIdeaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', fn (): array => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ]),
            'title' => $this->title,
            'abstract' => $this->abstract,
            'description' => $this->description,
            'team_size' => $this->team_size,
            'required_skills' => $this->required_skills,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
