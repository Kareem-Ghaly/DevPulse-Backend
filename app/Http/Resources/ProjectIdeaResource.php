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
           
            'title' => $this->title,
            'abstract' => $this->abstract,
            'description' => $this->description,
            'tech_stack' => $this->tech_stack,
            'required_skills' => $this->required_skills,
            'needed_roles' => $this->needed_roles,
            'domain' => $this->domain,
            'ai_keywords' => $this->ai_keywords,
            'ai_summary' => $this->ai_summary,
            'ai_analysis_status' => $this->ai_analysis_status,
            'ai_error' => $this->ai_error,
            'team_size' => $this->team_size,
            'status' => $this->status,
           
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
