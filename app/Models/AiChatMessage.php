<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'role',
        'body',
        'is_error',
    ];

    protected $casts = [
        'is_error' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * One ongoing conversation per logged-in user, or per browser session for guests.
     */
    public function scopeForOwner(Builder $query, ?int $userId, ?string $sessionId): Builder
    {
        return $userId
            ? $query->where('user_id', $userId)
            : $query->whereNull('user_id')->where('session_id', $sessionId);
    }

    public function scopeSameOwnerAs(Builder $query, self $message): Builder
    {
        return $query->forOwner($message->user_id, $message->session_id);
    }
}
