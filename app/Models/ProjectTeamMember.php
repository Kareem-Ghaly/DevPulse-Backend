<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_team_id', 'user_id', 'role'])]
class ProjectTeamMember extends Model
{
    public function team(): BelongsTo
    {
        return $this->belongsTo(ProjectTeam::class, 'project_team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
