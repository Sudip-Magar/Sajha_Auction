<?php

namespace App\Livewire\User;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Products extends Component
{
    use Toast, WithPagination;

    public function getListeners(): array
    {
        $userId = Auth::id();

        if (!$userId) {
            return [
                'userNotificationReceived' => '$refresh',
            ];
        }

        return [
            'userNotificationReceived' => '$refresh',
            "echo-notification:App.Models.User.{$userId}" => '$refresh',
        ];
    }

    public function mount(): mixed
    {
        if (!Auth::user()->is_seller) {
            return $this->redirect(route('home'), navigate: true);
        }

        return null;
    }

    public function deleteProduct(Product $product): void
    {
        if ((int)$product->seller_id !== (int)Auth::id()) {
            abort(403);
        }

        if ($product->is_approved) {
            $this->error('Approved products cannot be deleted. Please contact administration.');
            return;
        }

        // Clean up images from storage
        $product->loadMissing('images');
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $product->delete();
        $this->success('Product deleted successfully.');
    }

    public function render(): View
    {
        if (!Auth::user()?->is_seller) {
            $this->redirect(route('home'), navigate: true);
        }

        $userProducts = Product::with(['category', 'images', 'auction.traditionalAuction'])
            ->where('seller_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('livewire.user.products', [
            'products' => $userProducts,
        ]);
    }
}
