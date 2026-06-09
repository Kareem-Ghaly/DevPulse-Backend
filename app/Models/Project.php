<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'project_idea_id',
    'project_team_id',
    'owner_id',
    'title',
    'description',
    'status',
])]
class Project extends Model
{
    public function idea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'project_idea_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ProjectTeam::class, 'project_team_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function proposal(): HasOne
    {
        return $this->hasOne(Proposal::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
