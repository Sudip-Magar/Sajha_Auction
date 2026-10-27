<?php

namespace App\Livewire\User;

use App\Enums\ProductApprovalStatus;
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

    public bool $showUnlistProductModal = false;

    public bool $showDeleteProductModal = false;

    public ?int $confirmingProductId = null;

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

    public function confirmUnlistProduct(Product $product): void
    {
        $this->confirmingProductId = $product->id;
        $this->showUnlistProductModal = true;
    }

    public function runConfirmedUnlist(): void
    {
        $product = Product::find($this->confirmingProductId);
        $this->showUnlistProductModal = false;
        $this->confirmingProductId = null;

        if ($product) {
            $this->unlistProduct($product);
        }
    }

    public function confirmDeleteProduct(Product $product): void
    {
        $this->confirmingProductId = $product->id;
        $this->showDeleteProductModal = true;
    }

    public function runConfirmedProductDelete(): void
    {
        $product = Product::find($this->confirmingProductId);
        $this->showDeleteProductModal = false;
        $this->confirmingProductId = null;

        if ($product) {
            $this->deleteProduct($product);
        }
    }

    /**
     * Takes a live direct-sell listing down without deleting it - the seller
     * can bring it back later by editing and resubmitting it for approval
     * (Product::isEditableBySeller() / ManageProduct::save()).
     */
    public function unlistProduct(Product $product): void
    {
        if ((int) $product->seller_id !== (int) Auth::id()) {
            abort(403);
        }

        if (! $product->isDirectSell()) {
            $this->error('Only direct-sell listings can be removed from sale this way.');

            return;
        }

        if ($product->approval_status !== ProductApprovalStatus::APPROVED) {
            $this->warning('This listing is not currently live.');

            return;
        }

        $product->unlistBySeller();
        $this->success('Product removed from sale. Edit and resubmit it whenever you want it live again.');
    }

    public function deleteProduct(Product $product): void
    {
        if ((int) $product->seller_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($product->approval_status === ProductApprovalStatus::APPROVED) {
            $this->error('Approved products cannot be deleted. Please contact administration.');

            return;
        }

        // Clean up images from storage
        $product->loadMissing(['images', 'proofImages']);
        foreach ($product->images->merge($product->proofImages) as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $product->delete();
        $this->success('Product deleted successfully.');
    }

    public function render(): View
    {
        $user = Auth::user();
        $isSeller = (bool) $user?->is_seller;
        $isAuctionAllowed = (bool) $user?->is_auction_allowed;
        $sellerApplicationPending = (bool) $user?->seller_application_pending;

        $userProducts = $isSeller
            ? Product::with(['category', 'images', 'auction.traditionalAuction'])
                ->where('seller_id', Auth::id())
                ->latest()
                ->paginate(10)
            : null;

        return view('livewire.user.products', [
            'isSeller' => $isSeller,
            'isAuctionAllowed' => $isAuctionAllowed,
            'sellerApplicationPending' => $sellerApplicationPending,
            'products' => $userProducts,
        ]);
    }
}
