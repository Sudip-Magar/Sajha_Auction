<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auction extends Model
{
    protected $fillable = [
        'product_id',
        'auction_type',
        'quantity',
        'starts_at_en',
        'starts_at_np',
        'ends_at_en',
        'ends_at_np',
        'starting_bid',
        'current_bid',
        'starting_price_cents',
        'current_price_cents',
        'bid_increment_cents',
        'timer_seconds',
        'timer_extension_seconds',
        'status',
    ];

    protected $casts = [
        'starts_at_en' => 'datetime',
        'ends_at_en' => 'datetime',
        'starting_bid' => 'decimal:2',
        'current_bid' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
