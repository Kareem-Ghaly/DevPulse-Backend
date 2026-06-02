<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectIdeaMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_idea_id' => $this->project_idea_id,
            'student_id' => $this->student_id,
           
            'match_score' => (float) $this->match_score,
            'matched_skills' => $this->matched_skills,
            'missing_skills' => $this->missing_skills,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
