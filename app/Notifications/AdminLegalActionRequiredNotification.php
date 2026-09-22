<?php

namespace App\Notifications;

use App\Models\DamagePenalty;

class AdminLegalActionRequiredNotification extends BroadcastDatabaseNotification
{
    public function __construct(protected DamagePenalty $penalty) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'Legal action required: '.$this->penalty->seller->name." did not pay the damage penalty for order #{$this->penalty->order->order_number} within 7 days. Their account has been deactivated.",
            'type' => 'legal_action_required',
            'order_id' => $this->penalty->order_id,
            'damage_penalty_id' => $this->penalty->id,
            'seller_id' => $this->penalty->seller_id,
        ];
    }
}
