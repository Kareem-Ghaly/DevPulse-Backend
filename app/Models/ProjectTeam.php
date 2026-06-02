<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_idea_id', 'leader_id', 'status'])]
class ProjectTeam extends Model
{
    public function projectIdea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class);
    }
}
