<?php

namespace Database\Factories;

use App\Enums\DamagePenaltyStatus;
use App\Models\DamagePenalty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DamagePenalty>
 */
class DamagePenaltyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => 15000,
            'status' => DamagePenaltyStatus::PENDING,
            'verdict_recorded_at' => now(),
            'due_at' => now()->addDays(7),
            'legal_action_flagged' => false,
            'prior_is_auction_allowed' => false,
            'prior_is_seller' => true,
        ];
    }
}
