<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'project_team_id',
    'title',
    'description',
    'status',
    'priority',
    'assigned_to',
    'created_by',
    'due_date',
    'completed_at',
    'completed_by',
    'completion_notes',
    'last_update',
])]
class Task extends Model
{
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'last_update' => 'datetime',
        ];
    }

    public function projectTeam(): BelongsTo
    {
        return $this->belongsTo(ProjectTeam::class, 'project_team_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(TaskLink::class);
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(TaskReview::class);
    }

    public function latestReview(): HasOne
    {
        return $this->hasOne(TaskReview::class)->latestOfMany('reviewed_at');
    }
}