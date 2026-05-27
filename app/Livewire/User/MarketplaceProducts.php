<?php

namespace App\Livewire\User;

use App\Enums\ProductSaleType;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MarketplaceProducts extends Component
{
    use WithPagination;

    public function render(): View
    {
        $products = Product::with(['category', 'images', 'user'])
            ->where('listing_type', ProductSaleType::DIRECT_SELLER->value)
            ->where('is_approved', true)
            ->where('status', 'active')
            ->latest()
            ->paginate(12);

        return view('livewire.user.marketplace-products', [
            'products' => $products,
        ]);
    }
}
