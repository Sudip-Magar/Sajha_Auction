<?php

namespace App\Models;

use App\Enums\PaymentTransactionMethod;
use App\Enums\PaymentTransactionParty;
use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    /** @use HasFactory<PaymentTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'amount',
        'payment_method',
        'status',
        'reference',
        'party',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'type' => PaymentTransactionType::class,
        'payment_method' => PaymentTransactionMethod::class,
        'status' => PaymentTransactionStatus::class,
        'party' => PaymentTransactionParty::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type->label();
    }
}
