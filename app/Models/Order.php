<?php

namespace App\Models;

use App\Enums\HandoverType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'buyer_id',
        'seller_id',
        'auction_id',
        'status',
        'total_amount',
        'deposit_amount',
        'deposit_status',
        'deposit_transaction_uuid',
        'payment_method',
        'payment_status',
        'handover_type',
        'meetup_location',
        'meetup_time',
        'meetup_time_np',
        'shipping_address',
        'buyer_phone',
        'notes',
        'cancellation_reason_category',
        'cancellation_note',
        'complaint_status',
        'complaint_resolution_note',
        'stale_notified_at',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'deposit_amount' => 'float',
        'meetup_time' => 'datetime',
        'stale_notified_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Order $order): void {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
            }
        });
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->orderBy('id');
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class);
    }

    public function sellerDebts(): HasMany
    {
        return $this->hasMany(SellerDebt::class, 'related_order_id');
    }

    /**
     * Sum of completed transactions of the given type(s) for this order.
     */
    public function transactionTotal(string ...$types): float
    {
        return round((float) $this->transactions()
            ->where('status', 'completed')
            ->whereIn('type', $types)
            ->sum('amount'), 2);
    }

    /** Money the buyer has paid online (held by the admin). */
    public function paidOnline(): float
    {
        return $this->transactionTotal(PaymentTransaction::TYPE_DEPOSIT_PAID);
    }

    /** Cash the buyer has handed over at the meetup. */
    public function paidCash(): float
    {
        return $this->transactionTotal(PaymentTransaction::TYPE_BALANCE_PAID_CASH);
    }

    public function totalPaid(): float
    {
        return round($this->paidOnline() + $this->paidCash(), 2);
    }

    /** What is still due; the cash due at handover while the order is open. */
    public function remainingAmount(): float
    {
        return max(0.0, round((float) $this->total_amount - $this->totalPaid(), 2));
    }

    /** The smallest online payment allowed: the configured deposit percentage of the winning bid. */
    public function minimumDeposit(): float
    {
        return $this->deposit_amount > 0
            ? (float) $this->deposit_amount
            : round((float) $this->total_amount * (float) config('services.esewa.deposit_percentage', 10) / 100, 2);
    }

    /** Whether the buyer may still make an online eSewa payment on this order. */
    public function acceptsOnlinePayment(): bool
    {
        return in_array($this->deposit_status, ['pending', 'paid'], true)
            && ! in_array($this->status, ['cancelled', 'completed'], true)
            && $this->remainingAmount() > 0;
    }

    public function needsDeposit(): bool
    {
        return $this->deposit_status === 'pending' && $this->status !== 'cancelled';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300',
            'confirmed' => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/40 dark:text-blue-300',
            'meetup_scheduled' => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950/40 dark:text-purple-300',
            'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300',
            'cancelled' => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/40 dark:text-rose-300',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return OrderStatus::labelFor($this->status);
    }

    public function getHandoverLabelAttribute(): string
    {
        return HandoverType::labelFor($this->handover_type);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return PaymentMethod::labelFor($this->payment_method);
    }
}
