<?php

namespace App\Livewire\User;

use App\Models\CartItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Cart extends Component
{
    use Toast;

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
                ->get()
            : collect();

        $subtotal = $cartItems->sum(function (CartItem $item): float {
            $price = (float) ($item->product?->sale_price ?? 0);

            return $price * $item->quantity;
        });

        return view('livewire.user.cart', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
        ]);
    }
}
