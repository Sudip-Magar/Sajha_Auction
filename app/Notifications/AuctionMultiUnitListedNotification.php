<?php

namespace App\Notifications;

use App\Models\Product;

/**
 * Sent to the SELLER themselves right after they list a new auction with
 * quantity > 1, so they understand upfront that the auction is for the
 * whole lot - the winner receives all of it, not one unit per bid.
 */
class AuctionMultiUnitListedNotification extends BroadcastDatabaseNotification
{
    public function __construct(public Product $product) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Heads up: your auction '{$this->product->name}' is listed with {$this->product->quantity} units. The winner of the auction will receive all {$this->product->quantity} units - the lot cannot be split between multiple bidders.",
            'type' => 'auction_multi_unit_listed',
            'product_id' => $this->product->id,
        ];
    }
}
