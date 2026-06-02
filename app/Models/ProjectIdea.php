<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['owner_id', 'title', 'abstract', 'description', 'team_size', 'required_skills'])]
class ProjectIdea extends Model
{
    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(ProjectIdeaMatch::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ProjectJoinRequest::class);
    }

    public function team(): HasOne
    {
        return $this->hasOne(ProjectTeam::class);
    }
}
