<?php

namespace App\Livewire\Admin;

use App\Enums\ProductApprovalStatus;
use App\Models\Product;
use App\Notifications\ProductApprovedNotification;
use App\Notifications\ProductCorrectionRequestedNotification;
use App\Notifications\ProductRejectedNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class Products extends Component
{
    use Toast, WithPagination;

    public string $search = '';

    public ?int $decidingProductId = null;

    public bool $showDecisionModal = false;

    public string $decisionReason = '';

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

    public function approveProduct(Product $product): void
    {
        $product->loadMissing(['auction.traditionalAuction']);
        $product->approveListing();

        $this->notifySeller($product, new ProductApprovedNotification($product));

        $this->success("Product '{$product->name}' has been approved.");
    }

    public function openRejectForm(int $productId): void
    {
        $this->decidingProductId = $productId;
        $this->decisionReason = '';
        $this->showDecisionModal = true;
    }

    public function cancelDecision(): void
    {
        $this->decidingProductId = null;
        $this->decisionReason = '';
        $this->showDecisionModal = false;
    }

    public function rejectProduct(): void
    {
        $product = $this->decidingProduct();
        if (! $product) {
            return;
        }

        $reason = $this->validatedDecisionReason();
        if ($reason === null) {
            $this->error('Please provide a reason for rejecting this product.');

            return;
        }

        $product->rejectListing($reason);
        $this->notifySeller($product, new ProductRejectedNotification($product, $reason));

        $this->cancelDecision();
        $this->success("Product '{$product->name}' has been rejected.");
    }

    public function requestCorrection(): void
    {
        $product = $this->decidingProduct();
        if (! $product) {
            return;
        }

        $reason = $this->validatedDecisionReason();
        if ($reason === null) {
            $this->error('Please describe what needs to be corrected.');

            return;
        }

        $product->requestCorrection($reason);
        $this->notifySeller($product, new ProductCorrectionRequestedNotification($product, $reason));

        $this->cancelDecision();
        $this->success("Correction requested for '{$product->name}'.");
    }

    private function decidingProduct(): ?Product
    {
        if (! $this->decidingProductId) {
            return null;
        }

        return Product::find($this->decidingProductId);
    }

    /**
     * Trims and enforces the same 255-char limit the remarks column stores,
     * so a long reason gets a friendly validation error instead of silent
     * truncation. Returns null when the reason is unusable.
     */
    private function validatedDecisionReason(): ?string
    {
        $reason = trim($this->decisionReason);

        if ($reason === '') {
            return null;
        }

        $this->validate(['decisionReason' => 'max:255'], [
            'decisionReason.max' => 'Please keep the reason under 255 characters.',
        ]);

        return $reason;
    }

    private function notifySeller(Product $product, object $notification): void
    {
        if (! $product->user) {
            return;
        }

        try {
            $product->user->notify($notification);
        } catch (BroadcastException $exception) {
            Log::warning('Product decision notification broadcast failed.', [
                'product_id' => $product->id,
                'user_id' => $product->user->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function render()
    {
        $products = Product::with(['user', 'category', 'images', 'auction.traditionalAuction'])
            ->where('name', 'like', '%'.$this->search.'%')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.products', [
            'products' => $products,
            'approvalStatuses' => ProductApprovalStatus::cases(),
        ]);
    }
}
