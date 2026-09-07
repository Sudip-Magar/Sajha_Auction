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
        'winning_price',
        'total_bids',
        'status',
        'settlement_reason',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'extended_end_time' => 'datetime',
        'current_price' => 'decimal:2',
        'winning_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function traditionalAuction(): HasOne
    {
        return $this->hasOne(TraditionalAuction::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * The deadline actually in force: the anti-sniping extension if one has
     * been triggered, otherwise the originally scheduled end time. Every
     * "is this auction still open" check must go through this, not
     * `end_time` directly, or a last-second extension has no real effect.
     */
    public function getEffectiveEndTimeAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->extended_end_time ?? $this->end_time;
    }

    public function isLive(): bool
    {
        $now = now();

        return $this->status === 'active'
            && $this->start_time <= $now
            && $this->effective_end_time >= $now;
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'pending' || ($this->status === 'active' && $this->start_time > now());
    }

    public function isEnded(): bool
    {
        return in_array($this->status, ['ended', 'completed', 'ended_unsold'], true)
            || ($this->effective_end_time && $this->effective_end_time < now());
    }

    /**
     * Algorithm 2: Dynamic Step Function Minimum Increment \Delta(p)
     */
    public function getStepIncrement(): float
    {
        $price = (float) $this->current_price;
        $customIncrement = (float) ($this->traditionalAuction?->min_bid_increment ?? 0);

        if ($price < 1000) {
            $step = 50.0;
        } elseif ($price < 5000) {
            $step = 100.0;
        } elseif ($price < 20000) {
            $step = 250.0;
        } elseif ($price < 50000) {
            $step = 500.0;
        } else {
            $step = 1000.0;
        }

        return max($step, $customIncrement);
    }

    public function getMinNextBid(): float
    {
        return (float) ($this->current_price + $this->getStepIncrement());
    }
}
