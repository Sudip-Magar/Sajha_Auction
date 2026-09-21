<?php

namespace App\Models;

use Database\Factories\SellerDebtFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerDebt extends Model
{
    /** @use HasFactory<SellerDebtFactory> */
    use HasFactory;

    public const STATUS_OUTSTANDING = 'outstanding';

    public const STATUS_RECOVERED = 'recovered';

    protected $fillable = [
        'seller_id',
        'related_order_id',
        'amount',
        'recovered_amount',
        'reason',
        'status',
        'overdue_notified_at',
        'recovered_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'recovered_amount' => 'float',
        'overdue_notified_at' => 'datetime',
        'recovered_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }

    public function getRemainingAttribute(): float
    {
        return round($this->amount - $this->recovered_amount, 2);
    }
}
