<?php

namespace App\Notifications;

use App\Models\ComplaintMessage;

class ComplaintMessageReceivedNotification extends BroadcastDatabaseNotification
{
    public function __construct(public ComplaintMessage $message) {}

    public function toDatabase(object $notifiable): array
    {
        $order = $this->message->order;

        return [
            'message' => "New message on the complaint for order #{$order?->order_number}: ".str($this->message->body)->limit(120),
            'type' => 'complaint_message_received',
            'order_id' => $order?->id,
        ];
    }
}
