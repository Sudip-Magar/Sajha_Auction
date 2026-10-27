<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Shared shape for the app's database+broadcast notifications: only
 * toDatabase() actually differs between them, so subclasses define just
 * that and get via()/toArray()/toBroadcast() for free.
 *
 * Several older notifications in this app repeat this same boilerplate
 * directly rather than extending this base - left as-is since converting
 * them isn't part of this change, but new notifications should extend this.
 */
abstract class BroadcastDatabaseNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function toDatabase(object $notifiable): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))->onConnection('sync');
    }
}
