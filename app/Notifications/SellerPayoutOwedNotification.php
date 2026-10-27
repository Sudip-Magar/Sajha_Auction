<?php

namespace App\Notifications;

use App\Models\PayoutRequest;

/**
 * Sent to the seller whenever admin creates a payout request for them on an
 * auction order (full sale proceeds on completion, or their share of a
 * forfeited deposit on cancellation) - prompts them to submit their eSewa
 * details so admin can actually send the money.
 */
class SellerPayoutOwedNotification extends BroadcastDatabaseNotification
{
    public function __construct(public PayoutRequest $payout) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Rs. {$this->formattedAmount()} is ready to be paid out for order #{$this->payout->order->order_number} ({$this->payout->purpose_label}). Submit your eSewa details to receive it.",
            'type' => 'seller_payout_owed',
            'order_id' => $this->payout->order_id,
            'payout_id' => $this->payout->id,
        ];
    }

    public function formattedAmount(): string
    {
        return number_format($this->payout->amount, 2);
    }
}
