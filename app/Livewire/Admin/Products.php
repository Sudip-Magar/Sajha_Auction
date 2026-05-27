<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use App\Notifications\ProductApprovedNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class Products extends Component
{
    use Toast, WithPagination;

    public function getListeners(): array
    {
        $adminId = auth()->guard('admin')->id();

        if (! $adminId) {
            return [
                'adminNotificationReceived' => '$refresh',
            ];
        }

        return [
            'adminNotificationReceived' => '$refresh',
            "echo-notification:App.Models.Admin.{$adminId}" => '$refresh',
        ];
    }

    public string $search = '';

    public function approveProduct(Product $product): void
    {
        $product->loadMissing(['auction', 'pennyAuction', 'traditionalAuction']);
        $product->approveListing();

        $product->user?->notify(new ProductApprovedNotification($product));

        $this->success("Product '{$product->name}' has been approved.");
    }

    public function render()
    {
        $products = Product::with(['user', 'category', 'images', 'directSellerProduct', 'pennyAuction', 'traditionalAuction'])
            ->where('name', 'like', '%'.$this->search.'%')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.products', [
            'products' => $products,
        ]);
    }
}
