<?php

namespace App\Livewire\User;

use App\Enums\OrderDepositStatus;
use App\Models\Order;
use App\Services\OrderPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Orders extends Component
{
    use Toast, WithPagination;

    public string $tab = 'purchases';

    public ?int $confirmingCompleteOrderId = null;

    public bool $showCompleteConfirm = false;

    public function mount(): void
    {
        if (session()->has('esewa_success')) {
            $this->success(session('esewa_success'));
        } elseif (session()->has('esewa_error')) {
            $this->error(session('esewa_error'));
        }
    }

    public function requestMarkCompleted(int $orderId): void
    {
        $this->confirmingCompleteOrderId = $orderId;
        $this->showCompleteConfirm = true;
    }

    public function confirmMarkCompleted(): void
    {
        $this->showCompleteConfirm = false;

        if ($this->confirmingCompleteOrderId === null) {
            return;
        }

        $this->updateOrderStatus($this->confirmingCompleteOrderId, 'completed');
        $this->confirmingCompleteOrderId = null;
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

        // Same precondition as OrderDetail::updateOrderStatus(): an auction
        // win isn't ready to confirm until the buyer has paid at least the
        // minimum deposit via eSewa and scheduled a meetup.
        if ($status === 'confirmed' && $order->auction_id) {
            if ($order->deposit_status !== OrderDepositStatus::PAID) {
                $this->error('You cannot confirm this order yet - the buyer has not paid the auction deposit via eSewa.');

                return;
            }

            if (! $order->meetup_time) {
                $this->error('You cannot confirm this order yet - the buyer has not scheduled a meetup location, date, and time.');

                return;
            }
        }

        // The handover can't be marked done before the meetup it describes
        // has actually happened - applies to every order, auction or
        // direct-sell, see Order::meetupDateHasArrived().
        if ($status === 'completed' && ! $order->meetupDateHasArrived()) {
            $this->error('You can mark this order completed once the scheduled meetup date arrives.');

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
            $purchases = new LengthAwarePaginator(collect(), 0, 10);
            $sales = new LengthAwarePaginator(collect(), 0, 10);
        } else {
            // Each tab paginates independently, with its own page query-string
            // parameter, so switching tabs doesn't reset the other tab's page.
            $purchases = Order::where('buyer_id', $user->id)
                ->with(['items.product.images', 'seller', 'buyer'])
                ->latest()
                ->paginate(10, pageName: 'purchases-page');

            $sales = Order::where('seller_id', $user->id)
                ->with(['items.product.images', 'seller', 'buyer'])
                ->latest()
                ->paginate(10, pageName: 'sales-page');
        }

        return view('livewire.user.orders', [
            'purchases' => $purchases,
            'sales' => $sales,
        ]);
    }
}
