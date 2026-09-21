<?php

namespace App\Models;

use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    /** @use HasFactory<PaymentTransactionFactory> */
    use HasFactory;

    public const TYPE_DEPOSIT_PAID = 'deposit_paid';

    public const TYPE_BALANCE_PAID_CASH = 'balance_paid_cash';

    public const TYPE_REFUND_ISSUED = 'refund_issued';

    public const TYPE_DEPOSIT_FORFEITED = 'deposit_forfeited';

    public const TYPE_PAYOUT_SENT = 'payout_sent';

    public const TYPE_DEBT_RECORDED = 'debt_recorded';

    public const TYPE_DEBT_RECOVERED = 'debt_recovered';

    protected $fillable = [
        'order_id',
        'type',
        'amount',
        'payment_method',
        'status',
        'reference',
        'party',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT_PAID => 'Paid online via eSewa',
            self::TYPE_BALANCE_PAID_CASH => 'Paid in cash at handover',
            self::TYPE_REFUND_ISSUED => 'Refund to buyer',
            self::TYPE_DEPOSIT_FORFEITED => 'Deposit forfeited',
            self::TYPE_PAYOUT_SENT => 'Payout sent',
            self::TYPE_DEBT_RECORDED => 'Seller debt recorded',
            self::TYPE_DEBT_RECOVERED => 'Seller debt recovered',
            default => str($this->type)->replace('_', ' ')->title()->toString(),
        };
    }
}
