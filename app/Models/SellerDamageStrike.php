<?php

namespace App\Models;

use Database\Factories\SellerDamageStrikeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerDamageStrike extends Model
{
    /** @use HasFactory<SellerDamageStrikeFactory> */
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'order_id',
        'damage_penalty_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function damagePenalty(): BelongsTo
    {
        return $this->belongsTo(DamagePenalty::class);
    }
}
