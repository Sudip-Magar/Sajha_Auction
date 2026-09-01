<?php

namespace App\Livewire\User;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class OrderDetail extends Component
{
    use Toast;

    public Order $order;

    public function mount(Order $order): void
    {
        $user = Auth::user();
        if (! $user || ((int) $order->buyer_id !== (int) $user->id && (int) $order->seller_id !== (int) $user->id)) {
            abort(403);
        }

        $this->order = $order->load([
            'buyer',
            'seller',
            'items.product.images',
            'items.product.timelines',
        ]);
    }

    public function updateOrderStatus(string $status): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $oldStatus = $this->order->status;
        $this->order->update(['status' => $status]);

        if ($status === 'completed') {
            $this->order->update(['payment_status' => 'paid']);

            foreach ($this->order->items as $item) {
                if ($item->product) {
                    $item->product->decrement('quantity', min($item->quantity, $item->product->quantity));
                    if ($item->product->quantity <= 0) {
                        $item->product->update(['status' => 'sold']);
                    }

                    $item->product->logTimeline(
                        'completed',
                        "Product Handed Over & Sold (#{$this->order->order_number})",
                        "Order successfully completed by buyer {$this->order->buyer?->name} and seller {$this->order->seller?->name}.",
                        $user
                    );
                }
            }
        } elseif ($status === 'confirmed') {
            foreach ($this->order->items as $item) {
                $item->product?->logTimeline(
                    'meetup_scheduled',
                    "Order Confirmed by Seller (#{$this->order->order_number})",
                    'Seller confirmed order. Agreed meetup location: '.($this->order->meetup_location ?: 'Seller Location').'.',
                    $user
                );
            }
        } elseif ($status === 'cancelled') {
            foreach ($this->order->items as $item) {
                $item->product?->logTimeline(
                    'cancelled',
                    "Order Cancelled (#{$this->order->order_number})",
                    'Order was cancelled.',
                    $user
                );
            }
        }

        $this->order->refresh();
        $this->success("Order status updated to '".str($status)->replace('_', ' ')->title()."'.");
    }

    public function render(): View
    {
        return view('livewire.user.order-detail');
    }
}
