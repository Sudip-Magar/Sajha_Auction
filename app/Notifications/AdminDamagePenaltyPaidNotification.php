<?php

namespace App\Notifications;

use App\Models\DamagePenalty;

/**
 * Real-time (Reverb) alert to admins the moment a seller's damage-penalty
 * payment clears, so they know to review and restore access.
 */
class AdminDamagePenaltyPaidNotification extends BroadcastDatabaseNotification
{
    public function __construct(protected DamagePenalty $penalty, protected int $strikeCount) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => $this->penalty->seller->name.' paid the damage penalty of Rs. '
                .number_format($this->penalty->amount, 2)." for order #{$this->penalty->order->order_number}. "
                ."This is strike {$this->strikeCount} of 3. Review and restore their access.",
            'type' => 'damage_penalty_paid',
            'order_id' => $this->penalty->order_id,
            'damage_penalty_id' => $this->penalty->id,
            'seller_id' => $this->penalty->seller_id,
            'strike_count' => $this->strikeCount,
        ];
    }
}
