<?php

namespace App\Livewire\Admin;

use App\Enums\ProductApprovalStatus;
use App\Models\Product;
use App\Models\SellerWarning;
use App\Notifications\ProductApprovedNotification;
use App\Notifications\ProductCorrectionRequestedNotification;
use App\Notifications\ProductRejectedNotification;
use App\Notifications\SellerWarningIssuedNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class ProductDetail extends Component
{
    use Toast;

    public Product $product;

    public bool $showDecisionForm = false;

    public string $decisionReason = '';

    public bool $showWarningForm = false;

    public string $warningReason = '';

    public function mount(Product $product): void
    {
        $this->product = $product->load(['user', 'category', 'images', 'auction.traditionalAuction']);
    }

    public function approveProduct(): void
    {
        if ($this->product->approval_status === ProductApprovalStatus::APPROVED) {
            $this->warning('This product is already approved.');

            return;
        }

        $this->product->approveListing();
        $this->refreshProduct();
        $this->notifySeller(new ProductApprovedNotification($this->product));

        $this->success("Product '{$this->product->name}' has been approved.");
    }

    public function toggleDecisionForm(): void
    {
        $this->showDecisionForm = ! $this->showDecisionForm;
        $this->decisionReason = '';
    }

    public function rejectProduct(): void
    {
        $reason = $this->validatedDecisionReason();
        if ($reason === null) {
            $this->error('Please provide a reason for rejecting this product.');

            return;
        }

        $this->product->rejectListing($reason);
        $this->refreshProduct();
        $this->notifySeller(new ProductRejectedNotification($this->product, $reason));

        $this->showDecisionForm = false;
        $this->decisionReason = '';
        $this->success("Product '{$this->product->name}' has been rejected.");
    }

    public function requestCorrection(): void
    {
        $reason = $this->validatedDecisionReason();
        if ($reason === null) {
            $this->error('Please describe what needs to be corrected.');

            return;
        }

        $this->product->requestCorrection($reason);
        $this->refreshProduct();
        $this->notifySeller(new ProductCorrectionRequestedNotification($this->product, $reason));

        $this->showDecisionForm = false;
        $this->decisionReason = '';
        $this->success("Correction requested for '{$this->product->name}'.");
    }

    public function toggleFeatured(): void
    {
        $this->product->update([
            'is_featured' => ! $this->product->is_featured,
        ]);

        $this->refreshProduct();
        $this->success('Featured status updated.');
    }

    public function toggleTrending(): void
    {
        $this->product->update([
            'is_trending' => ! $this->product->is_trending,
        ]);

        $this->refreshProduct();
        $this->success('Trending status updated.');
    }

    public function toggleWarningForm(): void
    {
        $this->showWarningForm = ! $this->showWarningForm;
        $this->warningReason = '';
    }

    public function sendWarning(): void
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin || ! $this->product->user) {
            return;
        }

        $reason = trim($this->warningReason);

        if ($reason === '') {
            $this->error('Please describe the reason for this warning.');

            return;
        }

        $this->validate(['warningReason' => 'max:500'], [
            'warningReason.max' => 'Please keep the warning under 500 characters.',
        ]);

        $warning = SellerWarning::create([
            'seller_id' => $this->product->seller_id,
            'admin_id' => $admin->id,
            'product_id' => $this->product->id,
            'reason' => $reason,
        ]);

        $this->notifySeller(new SellerWarningIssuedNotification($warning));

        $this->showWarningForm = false;
        $this->warningReason = '';
        $this->success("Warning sent to {$this->product->user->name}.");
    }

    public function render(): View
    {
        return view('livewire.admin.product-detail');
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

    private function refreshProduct(): void
    {
        $this->product->refresh()->load(['user', 'category', 'images', 'auction.traditionalAuction']);
    }

    private function notifySeller(object $notification): void
    {
        if (! $this->product->user) {
            return;
        }

        try {
            $this->product->user->notify($notification);
        } catch (BroadcastException $exception) {
            Log::warning('Product decision notification broadcast failed.', [
                'product_id' => $this->product->id,
                'user_id' => $this->product->user->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
