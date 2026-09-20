<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewOrderReceivedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(protected Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->order->loadMissing(['buyer', 'items.product']);

        $productNames = $this->order->items
            ->map(fn ($item) => $item->product?->name)
            ->filter()
            ->implode(', ');

        $buyerName = $this->order->buyer?->name ?? 'A buyer';

        return [
            'message' => "{$buyerName} placed order #{$this->order->order_number} for {$productNames}.",
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'buyer_id' => $this->order->buyer_id,
            'buyer_name' => $buyerName,
            'buyer_phone' => $this->order->buyer_phone,
            'product_names' => $productNames,
            'total_amount' => $this->order->total_amount,
            'handover_type' => $this->order->handover_type,
            'type' => 'new_order_received',
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
