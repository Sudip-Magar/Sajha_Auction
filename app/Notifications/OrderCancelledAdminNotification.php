<?php

namespace App\Notifications;

use App\Models\Order;

/**
 * Sent to every admin when an auction order is cancelled, whichever path it
 * took (outright forfeit or a complaint under review) - a general heads-up
 * distinct from ComplaintFiledNotification, which only covers the
 * complaint-specific verdict-needed case.
 */
class OrderCancelledAdminNotification extends BroadcastDatabaseNotification
{
    public function __construct(public Order $order) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Auction order #{$this->order->order_number} was cancelled"
                .($this->order->cancellation_reason_category ? ' ('.$this->order->cancellation_reason_category->label().')' : '')
                .'.',
            'type' => 'order_cancelled_admin',
            'order_id' => $this->order->id,
        ];
    }
}
