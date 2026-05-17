<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SellerRegisteredNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast']; // both persist + real-time
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => $this->user->name.' wants to register as a seller',
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_avatar' => $this->user->avatar,
        ];
    }

    // broadcast channel uses this
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->user->name.' wants to register as a seller',
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_avatar' => $this->user->avatar,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))
            ->onConnection('sync');
    }

    public function broadcastType(): string
    {
        return 'seller-registered';
    }
}
