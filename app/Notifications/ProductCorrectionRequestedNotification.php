<?php

namespace App\Notifications;

use App\Models\Product;

class ProductCorrectionRequestedNotification extends BroadcastDatabaseNotification
{
    public function __construct(protected Product $product, protected string $reason) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Your product '{$this->product->name}' needs a correction before it can be approved: {$this->reason}",
            'product_id' => $this->product->id,
            'type' => 'product_correction_requested',
        ];
    }
}
