<?php

namespace App\Notifications;

use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewChatMessageNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(protected ChatMessage $message)
    {
        $this->message->loadMissing(['sender', 'conversation.product']);
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $senderName = $this->message->sender?->name ?? 'A user';
        $productName = $this->message->conversation?->product?->name ?? 'item';

        return [
            'message' => "{$senderName} sent you a message regarding '{$productName}'.",
            'conversation_id' => $this->message->conversation_id,
            'product_id' => $this->message->conversation?->product_id,
            'sender_id' => $this->message->sender_id,
            'body_snippet' => (string) str($this->message->body)->limit(40),
            'type' => 'chat_message',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))
            ->onConnection('sync');
    }
}
