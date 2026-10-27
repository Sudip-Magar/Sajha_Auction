<?php

namespace App\Models;

use App\Enums\PayoutPurpose;
use App\Enums\PayoutRecipientRole;
use App\Enums\PayoutStatus;
use Database\Factories\PayoutRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRequest extends Model
{
    /** @use HasFactory<PayoutRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'recipient_id',
        'recipient_role',
        'purpose',
        'amount',
        'debt_deducted',
        'esewa_name',
        'esewa_phone',
        'qr_image_path',
        'payout_status',
        'details_submitted_at',
        'sent_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'debt_deducted' => 'float',
        'recipient_role' => PayoutRecipientRole::class,
        'purpose' => PayoutPurpose::class,
        'payout_status' => PayoutStatus::class,
        'details_submitted_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function getPurposeLabelAttribute(): string
    {
        return $this->purpose->label();
    }
}
