<?php

namespace App\Notifications;

use App\Models\Order;

/**
 * Sent to every admin when an auction order is marked completed - the seller
 * is now owed a payout of the held deposit and needs one sent once they
 * submit their eSewa details.
 */
class OrderCompletedAdminNotification extends BroadcastDatabaseNotification
{
    public function __construct(public Order $order) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Auction order #{$this->order->order_number} was completed. The seller is owed a payout of the held deposit.",
            'type' => 'order_completed_admin',
            'order_id' => $this->order->id,
        ];
    }
}
