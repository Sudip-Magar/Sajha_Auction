<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewProductUploadedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    protected $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'New product uploaded: '.$this->product->name,
            'product_id' => $this->product->id,
            'user_name' => $this->product->user->name,
            'user_avatar' => $this->product->user->avatar,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'New product uploaded: '.$this->product->name,
            'product_id' => $this->product->id,
            'user_name' => $this->product->user->name,
            'user_avatar' => $this->product->user->avatar,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))
            ->onConnection('sync');
    }

    public function broadcastType(): string
    {
        return 'new-product-uploaded';
    }
}
