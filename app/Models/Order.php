<?php

namespace App\Models;

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
        'status',
        'total_amount',
        'payment_method',
        'payment_status',
        'handover_type',
        'meetup_location',
        'meetup_time',
        'shipping_address',
        'buyer_phone',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'meetup_time' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
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
        return match ($this->status) {
            'pending' => 'Pending Confirmation',
            'confirmed' => 'Order Confirmed',
            'meetup_scheduled' => 'Meetup Scheduled',
            'completed' => 'Completed & Handed Over',
            'cancelled' => 'Cancelled',
            default => str($this->status)->title(),
        };
    }
}
