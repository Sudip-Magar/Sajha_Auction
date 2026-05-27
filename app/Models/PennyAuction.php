<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PennyAuction extends Model
{
    protected $fillable = [
        'auction_id',
        'bid_cost_credits',
        'price_increment',
        'timer_start_seconds',
        'timer_reset_seconds',
        'max_bids_per_user',
        'credit_refund_on_loss',
    ];

    protected $casts = [
        'price_increment' => 'decimal:2',
        'credit_refund_on_loss' => 'boolean',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }
}
