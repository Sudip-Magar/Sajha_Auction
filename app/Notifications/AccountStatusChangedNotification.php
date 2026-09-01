<?php

namespace App\Notifications;

use App\Enums\StatusState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class AccountStatusChangedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(protected string $status) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => $this->status === StatusState::INACTIVE->value
                ? 'Your account has been marked inactive by the admin.'
                : 'Your account has been activated by the admin.',
            'type' => 'account_status_changed',
            'status' => $this->status,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))
            ->onConnection('sync');
    }
}
