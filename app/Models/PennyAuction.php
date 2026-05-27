<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PennyAuction extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'starting_price_cents',
        'bid_increment_cents',
        'timer_seconds',
        'timer_extension_seconds',
        'auction_start_en',
        'auction_start_np',
    ];

    protected $casts = [
        'auction_start_en' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
