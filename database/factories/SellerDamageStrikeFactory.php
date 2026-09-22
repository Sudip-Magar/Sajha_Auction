<?php

namespace Database\Factories;

use App\Models\SellerDamageStrike;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerDamageStrike>
 */
class SellerDamageStrikeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => 15000,
        ];
    }
}
