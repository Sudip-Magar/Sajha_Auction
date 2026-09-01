<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraditionalAuction extends Model
{
    protected $fillable = [
        'auction_id',
        'starting_bid',
        'reserve_price',
        'min_bid_increment',
        'timer_start_seconds',
        'timer_reset_seconds',
    ];

    protected $casts = [
        'starting_bid' => 'decimal:2',
        'reserve_price' => 'decimal:2',
        'min_bid_increment' => 'decimal:2',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }
}
