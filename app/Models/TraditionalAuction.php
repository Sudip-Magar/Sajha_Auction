<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraditionalAuction extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'starting_bid',
        'bid_increment',
        'auction_start_en',
        'auction_start_np',
        'auction_end_en',
        'auction_end_np',
    ];

    protected $casts = [
        'starting_bid' => 'decimal:2',
        'bid_increment' => 'decimal:2',
        'auction_start_en' => 'datetime',
        'auction_end_en' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
