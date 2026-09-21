<?php

namespace App\Livewire\User;

use App\Models\Order;
use App\Services\OrderPaymentService;
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

    public function mount(): void
    {
        if (session()->has('esewa_success')) {
            $this->success(session('esewa_success'));
        } elseif (session()->has('esewa_error')) {
            $this->error(session('esewa_error'));
        }
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        if (! in_array($status, ['confirmed', 'completed'], true)) {
            $this->error('Invalid order status. Cancelling requires a reason - use the order detail page.');

            return;
        }

        $order = Order::where('id', $orderId)
            ->where(function ($query) use ($user): void {
                $query->where('seller_id', $user->id)
                    ->orWhere('buyer_id', $user->id);
            })
            ->firstOrFail();

        if ((int) $order->seller_id !== (int) $user->id) {
            $this->error('Only the seller can update this order.');

            return;
        }

        if (in_array($order->status, ['cancelled', 'completed'], true)) {
            $this->error('This order can no longer be changed.');

            return;
        }

        if ($status === 'completed') {
            OrderPaymentService::complete($order, $user);
        } elseif ($status === 'confirmed') {
            $order->update(['status' => 'confirmed']);

            foreach ($order->items as $item) {
                $item->product?->logTimeline(
                    'meetup_scheduled',
                    "Order Confirmed by Seller (#{$order->order_number})",
                    'Seller confirmed order. Agreed meetup location: '.($order->meetup_location ?: 'Seller Location').'.',
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
