<?php

namespace App\Models;

use Database\Factories\PayoutRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRequest extends Model
{
    /** @use HasFactory<PayoutRequestFactory> */
    use HasFactory;

    public const PURPOSE_SELLER_FORFEIT_SHARE = 'seller_forfeit_share';

    public const PURPOSE_BUYER_REFUND = 'buyer_refund';

    public const STATUS_AWAITING_DETAILS = 'awaiting_details';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    /** The seller's share was fully offset by debt, so nothing is transferred. */
    public const STATUS_SETTLED = 'settled';

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
        return $this->purpose === self::PURPOSE_BUYER_REFUND
            ? 'Refund to buyer'
            : "Seller's share of the forfeited deposit";
    }
}
