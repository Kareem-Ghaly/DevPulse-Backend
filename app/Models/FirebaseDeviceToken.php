<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'token', 'token_hash', 'device_type', 'browser', 'last_used_at'])]
class FirebaseDeviceToken extends Model
{
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function setTokenAttribute(string $value): void
    {
        $this->attributes['token'] = $value;
        $this->attributes['token_hash'] = hash('sha256', $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}