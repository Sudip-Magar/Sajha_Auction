<?php

namespace App\Notifications;

use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OutbidNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(protected Auction $auction, protected float $newStandingPrice) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "You've been outbid on '{$this->auction->product?->name}'. Current price: Rs. ".number_format($this->newStandingPrice, 2).'.',
            'auction_id' => $this->auction->id,
            'product_id' => $this->auction->product_id,
            'type' => 'auction_outbid',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))->onConnection('sync');
    }
}
