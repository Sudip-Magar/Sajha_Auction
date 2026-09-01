<?php

namespace App\Livewire\User;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Wishlist as WishlistModel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Wishlist extends Component
{
    use Toast;

    public function removeFromWishlist(int $productId): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        WishlistModel::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->delete();

        $this->success('Product removed from your wishlist.');
        $this->dispatch('wishlistUpdated');
    }

    public function addToCart(int $productId): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->warning('Please login to add items to your cart.');
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        $product = Product::find($productId);
        if ($product && (int) $product->seller_id === (int) $user->id) {
            $this->error('You cannot add your own product to cart.');

            return;
        }

        $cartItem = CartItem::firstOrCreate(
            ['user_id' => $user->id, 'product_id' => $productId],
            ['quantity' => 1]
        );

        if (! $cartItem->wasRecentlyCreated) {
            $cartItem->increment('quantity');
        }

        $this->success('Product added to your cart!');
        $this->dispatch('cartUpdated');
    }

    public function render(): View
    {
        $user = Auth::user();
        $wishlistedProducts = $user
            ? $user->wishlistedProducts()
                ->with(['category', 'images', 'user'])
                ->latest('wishlists.created_at')
                ->get()
            : collect();

        return view('livewire.user.wishlist', [
            'products' => $wishlistedProducts,
        ]);
    }
}
