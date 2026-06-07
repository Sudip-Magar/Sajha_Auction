<?php

namespace App\Livewire\User;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductDetail extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        abort_unless($product->is_approved && $product->status === 'active', 404);

        $product->increment('views_count');

        $this->product = $product->refresh()->load([
            'auction.bids.bidder',
            'auction.traditionalAuction',
            'category',
            'images',
            'user',
        ]);
    }

    public function render(): View
    {
        return view('livewire.user.product-detail', [
            'similarProducts' => Product::with(['category', 'images'])
                ->where('is_approved', true)
                ->where('status', 'active')
                ->where('id', '!=', $this->product->id)
                ->where('category_id', $this->product->category_id)
                ->latest()
                ->take(4)
                ->get(),
        ]);
    }
}
