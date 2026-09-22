<?php

namespace App\Livewire\User;

use App\Models\CartItem;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Cart extends Component
{
    use Toast, WithPagination;

    public function updateQuantity(int $cartItemId, int $qty): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $cartItem = CartItem::where('id', $cartItemId)->where('user_id', $user->id)->first();
        if (! $cartItem) {
            return;
        }

        if ($qty <= 0) {
            $cartItem->delete();
            $this->success('Item removed from cart.');
        } else {
            $cartItem->update(['quantity' => min($qty, 99)]);
        }

        $this->dispatch('cartUpdated');
    }

    public function removeItem(int $cartItemId): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        CartItem::where('id', $cartItemId)->where('user_id', $user->id)->delete();
        $this->success('Item removed from cart.');
        $this->dispatch('cartUpdated');
    }

    public function render(): View
    {
        $user = Auth::user();
        $cartItems = $user
            ? $user->cartItems()
                ->with(['product.images', 'product.user', 'product.category'])
                ->latest()
                ->paginate(10)
            : new LengthAwarePaginator(collect(), 0, 10);

        // The subtotal and item count cover the whole cart, not just the
        // current page, so they are computed with one aggregate query
        // rather than re-fetching and hydrating every cart row again.
        $totals = $user
            ? $user->cartItems()
                ->join('products', 'products.id', '=', 'cart_items.product_id')
                ->selectRaw('COALESCE(SUM(cart_items.quantity * products.sale_price), 0) as subtotal, COALESCE(SUM(cart_items.quantity), 0) as total_items')
                ->first()
            : null;

        return view('livewire.user.cart', [
            'cartItems' => $cartItems,
            'subtotal' => (float) ($totals->subtotal ?? 0),
            'totalItemCount' => (int) ($totals->total_items ?? 0),
        ]);
    }
}
