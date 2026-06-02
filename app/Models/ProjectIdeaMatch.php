<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_idea_id', 'student_id', 'match_score', 'matched_skills', 'missing_skills'])]
class ProjectIdeaMatch extends Model
{
    protected function casts(): array
    {
        return [
            'match_score' => 'decimal:2',
            'matched_skills' => 'array',
            'missing_skills' => 'array',
        ];
    }

    public function projectIdea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
