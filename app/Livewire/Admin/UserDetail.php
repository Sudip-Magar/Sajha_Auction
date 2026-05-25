<?php

namespace App\Livewire\Admin;

use App\Enums\StatusState;
use App\Models\User;
use App\Notifications\AccountStatusChangedNotification;
use App\Notifications\AuctionApplicationApprovedNotification;
use App\Notifications\AuctionApplicationRejectedNotification;
use App\Notifications\SellerApprovedNotification;
use App\Notifications\SellerSuspendedNotification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class UserDetail extends Component
{
    use Toast;

    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user->load(['documentImages', 'products.images', 'products.category']);
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
        $isSeller = $nextAuctionState ? 1 : 0;

        $this->user->update([
            'is_auction_allowed' => $nextAuctionState,
            'is_seller' => $isSeller,
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

    public function render(): View
    {
        return view('livewire.admin.user-detail');
    }

    private function refreshUser(): void
    {
        $this->user->refresh()->load(['documentImages', 'products.images', 'products.category']);
    }
}
