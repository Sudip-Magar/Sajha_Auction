<?php

namespace App\Notifications;

use App\Models\DamagePenalty;

class SellerDamagePenaltyIssuedNotification extends BroadcastDatabaseNotification
{
    public function __construct(protected DamagePenalty $penalty) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'Your product on order #'.$this->penalty->order->order_number.' was found damaged/not working. '
                .'You have 7 days to pay a penalty of Rs. '.number_format($this->penalty->amount, 2).', '
                .'or your access will be permanently revoked and legal action will be taken.',
            'type' => 'damage_penalty_issued',
            'order_id' => $this->penalty->order_id,
            'damage_penalty_id' => $this->penalty->id,
            'amount' => $this->penalty->amount,
            'due_at' => $this->penalty->due_at->toDateTimeString(),
        ];
    }
}
