<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'full_name', 'academic_title', 'department', 'specialization', 'research_interests', 'office_hours', 'bio'])]
class SupervisorProfile extends Model
{
    protected function casts(): array
    {
        return [
            'research_interests' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
