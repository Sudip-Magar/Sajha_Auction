<?php

namespace App\Notifications;

use App\Models\Product;

class ProductRejectedNotification extends BroadcastDatabaseNotification
{
    public function __construct(protected Product $product, protected string $reason) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Your product '{$this->product->name}' was rejected. Reason: {$this->reason}",
            'product_id' => $this->product->id,
            'type' => 'product_rejected',
        ];
    }
}
