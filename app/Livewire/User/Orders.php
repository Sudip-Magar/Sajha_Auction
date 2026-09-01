<?php

namespace App\Livewire\User;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Orders extends Component
{
    use Toast;

    public string $tab = 'purchases';

    public function updateOrderStatus(int $orderId, string $status): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $order = Order::where('id', $orderId)
            ->where(function ($query) use ($user): void {
                $query->where('seller_id', $user->id)
                    ->orWhere('buyer_id', $user->id);
            })
            ->firstOrFail();

        $oldStatus = $order->status;
        $order->update(['status' => $status]);

        if ($status === 'completed') {
            $order->update(['payment_status' => 'paid']);

            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->decrement('quantity', min($item->quantity, $item->product->quantity));
                    if ($item->product->quantity <= 0) {
                        $item->product->update(['status' => 'sold']);
                    }

                    $item->product->logTimeline(
                        'completed',
                        "Product Handed Over & Sold (#{$order->order_number})",
                        "Order successfully completed by buyer {$order->buyer?->name} and seller {$order->seller?->name}.",
                        $user
                    );
                }
            }
        } elseif ($status === 'confirmed') {
            foreach ($order->items as $item) {
                $item->product?->logTimeline(
                    'meetup_scheduled',
                    "Order Confirmed by Seller (#{$order->order_number})",
                    'Seller confirmed order. Agreed meetup location: '.($order->meetup_location ?: 'Seller Location').'.',
                    $user
                );
            }
        } elseif ($status === 'cancelled') {
            foreach ($order->items as $item) {
                $item->product?->logTimeline(
                    'cancelled',
                    "Order Cancelled (#{$order->order_number})",
                    'Order was cancelled.',
                    $user
                );
            }
        }

        $this->success("Order status updated to '".str($status)->replace('_', ' ')->title()."'.");
    }

    public function render(): View
    {
        $user = Auth::user();
        if (! $user) {
            $purchases = collect();
            $sales = collect();
        } else {
            $purchases = Order::where('buyer_id', $user->id)
                ->with(['items.product.images', 'seller', 'buyer'])
                ->latest()
                ->get();

            $sales = Order::where('seller_id', $user->id)
                ->with(['items.product.images', 'seller', 'buyer'])
                ->latest()
                ->get();
        }

        return view('livewire.user.orders', [
            'purchases' => $purchases,
            'sales' => $sales,
        ]);
    }
}
