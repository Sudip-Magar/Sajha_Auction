<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DepositPaidNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(protected Order $order, protected ?float $amount = null) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'Rs. '.number_format($this->amount ?? (float) $this->order->deposit_amount, 2)." paid via eSewa for order #{$this->order->order_number}.".($this->order->remainingAmount() > 0
                ? ' Cash due at handover: Rs. '.number_format($this->order->remainingAmount(), 2).'.'
                : ' Paid in full; no cash is due at handover.'),
            'order_id' => $this->order->id,
            'type' => 'deposit_paid',
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
