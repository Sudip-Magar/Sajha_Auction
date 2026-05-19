<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use App\Notifications\ProductApprovedNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class ProductDetail extends Component
{
    use Toast;

    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['user', 'category', 'images']);
    }

    public function approveProduct(): void
    {
        if ($this->product->is_approved) {
            $this->warning('This product is already approved.');

            return;
        }

        $this->product->update([
            'is_approved' => true,
            'status' => 'active',
        ]);

        $this->product->refresh()->load(['user', 'category', 'images']);

        if ($this->product->user) {
            try {
                $this->product->user->notify(new ProductApprovedNotification($this->product));
            } catch (BroadcastException $exception) {
                Log::warning('Product approval notification broadcast failed.', [
                    'product_id' => $this->product->id,
                    'user_id' => $this->product->user->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $this->success("Product '{$this->product->name}' has been approved.");
    }

    public function render(): View
    {
        return view('livewire.admin.product-detail');
    }
}
