<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_team_id',
        'supervisor_id',
        'created_by',
        'last_updated_by',
        'title',
        'problem',
        'problem_overview',
        'comparison_table_with_similar_applications',
        'project_users',
        'mind_map_problem',
        'solution_overview',
        'proposed_solution',
        'mind_map_solution',
        'functional_requirements',
        'non_functional_requirements',
        'project_management',
        'programming_languages',
        'supervisor',
        'project_teams',
        'status',
        'supervisor_notes',
        'supervisor_decided_at',
        'last_update',
    ];

    protected $casts = [
        'last_update' => 'datetime',
        'supervisor_decided_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(ProjectTeam::class, 'project_team_id');
    }

    public function supervisorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastUpdater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function committeeReviews(): HasMany
    {
        return $this->hasMany(ProjectProposalCommitteeReview::class);
    }
}