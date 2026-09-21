<?php

namespace Database\Factories;

use App\Models\SellerDebt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerDebt>
 */
class SellerDebtFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => 1000,
            'recovered_amount' => 0,
            'reason' => 'Buyer complaint upheld',
            'status' => SellerDebt::STATUS_OUTSTANDING,
        ];
    }
}
