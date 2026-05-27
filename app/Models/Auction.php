<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function pennyAuction(): HasOne
    {
        return $this->hasOne(PennyAuction::class);
    }
}
