<?php

namespace App\Notifications;

use App\Models\Order;

class MeetupScheduledNotification extends BroadcastDatabaseNotification
{
    public function __construct(public Order $order) {}

    public function toDatabase(object $notifiable): array
    {
        $when = $this->order->meetup_time?->format('M d, Y \a\t h:i A') ?? 'a time to be confirmed';

        return [
            'message' => "The buyer set the meetup for order #{$this->order->order_number}: {$this->order->meetup_location} on {$when}.",
            'type' => 'meetup_scheduled',
            'order_id' => $this->order->id,
        ];
    }
}
