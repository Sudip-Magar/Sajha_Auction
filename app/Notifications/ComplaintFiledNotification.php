<?php

namespace App\Notifications;

use App\Models\Order;

class ComplaintFiledNotification extends BroadcastDatabaseNotification
{
    public function __construct(public Order $order) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "A complaint was filed on order #{$this->order->order_number}"
                .($this->order->cancellation_reason_category ? ' ('.$this->order->cancellation_reason_category->label().')' : '')
                .' and is awaiting your verdict.',
            'type' => 'complaint_filed',
            'order_id' => $this->order->id,
        ];
    }
}
