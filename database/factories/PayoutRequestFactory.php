<?php

namespace Database\Factories;

use App\Models\PayoutRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayoutRequest>
 */
class PayoutRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient_role' => 'seller',
            'purpose' => PayoutRequest::PURPOSE_SELLER_FORFEIT_SHARE,
            'amount' => 200,
            'debt_deducted' => 0,
            'payout_status' => PayoutRequest::STATUS_AWAITING_DETAILS,
        ];
    }
}
