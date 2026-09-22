<?php

namespace App\Models;

use App\Enums\ComplaintMessageSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintMessage extends Model
{
    protected $fillable = [
        'order_id',
        'sender_role',
        'sender_id',
        'body',
    ];

    protected $casts = [
        'sender_role' => ComplaintMessageSender::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Resolves to whichever model actually sent it - the order's own buyer
     * or the admin identified by sender_id. Not a single Eloquent relation
     * since sender_role points at two different tables.
     */
    public function sender(): Admin|User|null
    {
        return $this->sender_role === ComplaintMessageSender::ADMIN
            ? Admin::find($this->sender_id)
            : $this->order?->buyer;
    }
}
