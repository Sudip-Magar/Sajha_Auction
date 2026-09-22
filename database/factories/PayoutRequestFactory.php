<?php

namespace Database\Factories;

use App\Enums\PayoutPurpose;
use App\Enums\PayoutRecipientRole;
use App\Enums\PayoutStatus;
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
            'recipient_role' => PayoutRecipientRole::SELLER,
            'purpose' => PayoutPurpose::SELLER_FORFEIT_SHARE,
            'amount' => 200,
            'debt_deducted' => 0,
            'payout_status' => PayoutStatus::AWAITING_DETAILS,
        ];
    }
}
