<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalSubmission extends Model
{
    protected $fillable = [
        'project_team_id', 'proposal_drive_link', 'presentation_drive_link', 'code_drive_link',
        'student_notes', 'status', 'graded_by', 'graded_at',
        'proposal_grade', 'proposal_feedback', 'presentation_grade', 'presentation_feedback',
        'code_grade', 'code_feedback', 'total_grade'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(ProjectTeam::class, 'project_team_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}