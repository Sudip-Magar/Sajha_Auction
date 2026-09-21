<?php

namespace App\Events;

use App\Models\Auction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Auction $auction) {}

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
            'status' => $this->auction->status,
            'winner_id' => $this->auction->winner_id,
        ];
    }

    public function broadcastAs(): string
    {
        return 'auction.ended';
    }
}
