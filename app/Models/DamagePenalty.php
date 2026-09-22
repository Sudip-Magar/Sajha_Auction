<?php

namespace App\Models;

use App\Enums\DamagePenaltyStatus;
use Database\Factories\DamagePenaltyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DamagePenalty extends Model
{
    /** @use HasFactory<DamagePenaltyFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'amount',
        'transaction_uuid',
        'status',
        'verdict_recorded_at',
        'due_at',
        'paid_at',
        'expired_at',
        'legal_action_flagged',
        'prior_is_auction_allowed',
        'prior_is_seller',
        'restored_at',
        'restored_by_admin_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'status' => DamagePenaltyStatus::class,
        'verdict_recorded_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'legal_action_flagged' => 'boolean',
        'prior_is_auction_allowed' => 'boolean',
        'prior_is_seller' => 'boolean',
        'restored_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function restoredByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'restored_by_admin_id');
    }

    public function strikes(): HasMany
    {
        return $this->hasMany(SellerDamageStrike::class);
    }

    public function canBeRestored(): bool
    {
        return $this->status === DamagePenaltyStatus::PAID && $this->restored_at === null;
    }
}
