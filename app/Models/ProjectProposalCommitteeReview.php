<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProposalCommitteeReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_proposal_id',
        'committee_member_id',
        'decision',
        'notes',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function projectProposal()
    {
        return $this->belongsTo(ProjectProposal::class);
    }

    public function committeeMember()
    {
        return $this->belongsTo(User::class, 'committee_member_id');
    }
}