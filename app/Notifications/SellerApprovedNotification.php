<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SellerApprovedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'Congratulations! Your seller application has been approved.',
            'type' => 'seller_approved',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Congratulations! Your seller application has been approved.',
            'type' => 'seller_approved',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))
            ->onConnection('sync');
    }
}
