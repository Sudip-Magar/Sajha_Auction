<?php

namespace Database\Factories;

use App\Enums\PaymentTransactionType;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => PaymentTransactionType::DEPOSIT_PAID,
            'amount' => 1000,
            'payment_method' => 'esewa',
            'status' => 'completed',
        ];
    }
}
