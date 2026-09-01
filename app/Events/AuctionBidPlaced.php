<?php

namespace App\Events;

use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionBidPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Auction $auction;
    public Bid $bid;

    public function __construct(Auction $auction, Bid $bid)
    {
        $this->auction = $auction;
        $this->bid = $bid;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('auctions.' . $this->auction->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'current_price' => $this->auction->current_price,
            'total_bids' => $this->auction->total_bids,
        ];
    }
}
