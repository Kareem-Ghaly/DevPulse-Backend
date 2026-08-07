<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    protected $fillable = [
        'title', 'description', 'project_team_id', 'scheduled_by',
        'scheduler_role', 'scheduled_at', 'duration_minutes',
        'meeting_link', 'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(ProjectTeam::class, 'project_team_id');
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }
}