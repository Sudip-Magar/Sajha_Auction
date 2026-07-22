<?php

namespace App\Livewire\User;

use App\Models\Admin;
use App\Models\Product;
use App\Notifications\SellerRegisteredNotification;
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

        if (! $userId) {
            return [
                'userNotificationReceived' => '$refresh',
            ];
        }

        return [
            'userNotificationReceived' => '$refresh',
            "echo-notification:App.Models.User.{$userId}" => '$refresh',
        ];
    }

    public function requestSellerAccess(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if ($user->is_seller) {
            $this->info('Your account already has seller access.');

            return;
        }

        if ($user->seller_application_pending) {
            $this->warning('Your seller request is already pending review.');

            return;
        }

        $user->update([
            'seller_application_pending' => true,
        ]);

        $admins = Admin::all();
        foreach ($admins as $admin) {
            $admin->notify(new SellerRegisteredNotification($user));
        }

        $this->success('Seller access request sent for admin review.');
    }

    public function deleteProduct(Product $product): void
    {
        if ((int) $product->seller_id !== (int) Auth::id()) {
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
        $user = Auth::user();
        $isSeller = (bool) $user?->is_seller;
        $sellerApplicationPending = (bool) $user?->seller_application_pending;

        $userProducts = $isSeller
            ? Product::with(['category', 'images', 'auction.traditionalAuction'])
                ->where('seller_id', Auth::id())
                ->latest()
                ->paginate(10)
            : null;

        return view('livewire.user.products', [
            'isSeller' => $isSeller,
            'sellerApplicationPending' => $sellerApplicationPending,
            'products' => $userProducts,
        ]);
    }
}
