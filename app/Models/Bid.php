<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    protected $fillable = [
        'auction_id',
        'bidder_id',
        'bid_amount',
        'max_proxy_amount',
        'is_proxy',
        'ip_address',
        'placed_at',
    ];

    protected $casts = [
        'bid_amount' => 'decimal:2',
        'max_proxy_amount' => 'decimal:2',
        'is_proxy' => 'boolean',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function bidder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bidder_id');
    }
}
