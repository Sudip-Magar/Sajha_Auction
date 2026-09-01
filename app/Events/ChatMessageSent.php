<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
        $this->message->loadMissing(['sender', 'conversation']);
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('conversations.'.$this->message->conversation_id),
        ];

        if ($this->message->conversation) {
            $channels[] = new PrivateChannel('App.Models.User.'.$this->message->conversation->seller_id);
            $channels[] = new PrivateChannel('App.Models.User.'.$this->message->conversation->buyer_id);
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_id' => $this->message->sender_id,
                'sender_name' => $this->message->sender?->name ?? 'User',
                'body' => $this->message->body,
                'created_at' => $this->message->created_at?->format('M d, h:i A'),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }
}
