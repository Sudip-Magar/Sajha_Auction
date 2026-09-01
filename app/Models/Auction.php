<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Auction extends Model
{
    protected $fillable = [
        'product_id',
        'auction_type',
        'winner_id',
        'start_time',
        'start_time_np',
        'end_time',
        'end_time_np',
        'extended_end_time',
        'current_price',
        'total_bids',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'extended_end_time' => 'datetime',
        'current_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function traditionalAuction(): HasOne
    {
        return $this->hasOne(TraditionalAuction::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class)->latest();
    }

    public function isLive(): bool
    {
        $now = now();
        return $this->status === 'active' 
            && $this->start_time <= $now 
            && $this->end_time >= $now;
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'pending' || ($this->status === 'active' && $this->start_time > now());
    }

    public function getMinNextBid(): float
    {
        $increment = $this->traditionalAuction?->min_bid_increment ?? 0;
        return (float) ($this->current_price + $increment);
    }
}
