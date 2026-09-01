<?php

namespace App\Events;

use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionBidPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Auction $auction,
        public Bid $bid
    ) {
        $this->bid->loadMissing('bidder');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('auctions.'.$this->auction->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auction->id,
            'current_price' => (float) $this->auction->current_price,
            'total_bids' => (int) $this->auction->total_bids,
            'min_next_bid' => (float) $this->auction->getMinNextBid(),
            'bid' => [
                'id' => $this->bid->id,
                'bidder_name' => $this->bid->bidder?->name ?? 'Bidder',
                'bid_amount' => (float) $this->bid->bid_amount,
                'is_proxy' => (bool) $this->bid->is_proxy,
                'created_at' => $this->bid->created_at?->diffForHumans() ?? 'Just now',
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'auction.bid.placed';
    }
}
