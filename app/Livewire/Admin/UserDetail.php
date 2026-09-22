<?php

namespace App\Livewire\Admin;

use App\Enums\StatusState;
use App\Models\SellerWarning;
use App\Models\User;
use App\Notifications\AccountStatusChangedNotification;
use App\Notifications\AuctionApplicationApprovedNotification;
use App\Notifications\AuctionApplicationRejectedNotification;
use App\Notifications\SellerApprovedNotification;
use App\Notifications\SellerSuspendedNotification;
use App\Notifications\SellerWarningIssuedNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class UserDetail extends Component
{
    use Toast;

    public User $user;

    public bool $showWarningForm = false;

    public string $warningReason = '';

    public function mount(User $user): void
    {
        $this->user = $user->load(['documentImages', 'products.images', 'products.category', 'damagePenalties.order', 'warnings.admin', 'warnings.product']);
    }

    public function toggleStatus(): void
    {
        $nextStatus = $this->user->isActiveStatus()
            ? StatusState::INACTIVE->value
            : StatusState::ACTIVE->value;

        $this->user->update([
            'status' => $nextStatus,
        ]);

        $this->user->notify(new AccountStatusChangedNotification($nextStatus));

        $this->refreshUser();
        $this->success("User {$this->user->name} is now {$nextStatus}.");
    }

    public function toggleSellerAccess(): void
    {
        $nextSellerState = ! $this->user->is_seller;

        if ($nextSellerState && $this->user->is_permanently_banned) {
            $this->error("{$this->user->name} is permanently banned and cannot be re-enabled this way.");

            return;
        }

        $this->user->update([
            'is_seller' => $nextSellerState,
            'seller_application_pending' => false,
        ]);

        if ($nextSellerState) {
            $this->user->notify(new SellerApprovedNotification);
        } else {
            $this->user->notify(new SellerSuspendedNotification);
        }

        $this->refreshUser();
        $this->success($nextSellerState
            ? "{$this->user->name} now has seller access."
            : "{$this->user->name} has been suspended from seller access.");
    }

    public function toggleAuctionAccess(): void
    {
        $nextAuctionState = ! $this->user->is_auction_allowed;

        if ($nextAuctionState && $this->user->is_permanently_banned) {
            $this->error("{$this->user->name} is permanently banned and cannot be re-enabled this way.");

            return;
        }

        // Seller access and auction access are independent grants, toggled
        // by separate actions (toggleSellerAccess() above) - this used to
        // also overwrite is_seller here, silently re-enabling or revoking
        // seller access as a side effect of an unrelated auction toggle.
        $this->user->update([
            'is_auction_allowed' => $nextAuctionState,
        ]);

        if ($this->user->documentImages()->exists()) {
            $this->user->documentImages()->update([
                'is_approved' => $nextAuctionState,
                'is_rejected' => ! $nextAuctionState,
            ]);
        }

        if ($nextAuctionState) {
            $this->user->notify(new AuctionApplicationApprovedNotification);
        } else {
            $this->user->notify(new AuctionApplicationRejectedNotification);
        }

        $this->refreshUser();
        $this->success($nextAuctionState
            ? "{$this->user->name} can now join auctions."
            : "{$this->user->name} can no longer join auctions.");
    }

    public function toggleWarningForm(): void
    {
        $this->showWarningForm = ! $this->showWarningForm;
        $this->warningReason = '';
    }

    public function sendWarning(): void
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
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
            'seller_id' => $this->user->id,
            'admin_id' => $admin->id,
            'reason' => $reason,
        ]);

        try {
            $this->user->notify(new SellerWarningIssuedNotification($warning));
        } catch (BroadcastException $exception) {
            Log::warning('Seller warning notification could not be delivered.', [
                'seller_id' => $this->user->id,
                'warning_id' => $warning->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        $this->showWarningForm = false;
        $this->warningReason = '';
        $this->refreshUser();
        $this->success("Warning sent to {$this->user->name}.");
    }

    public function render(): View
    {
        return view('livewire.admin.user-detail');
    }

    private function refreshUser(): void
    {
        $this->user->refresh()->load(['documentImages', 'products.images', 'products.category', 'damagePenalties.order', 'warnings.admin', 'warnings.product']);
    }
}
