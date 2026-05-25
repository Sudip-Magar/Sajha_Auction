<?php

namespace App\Livewire\Components\User;

use App\Models\Admin;
use App\Notifications\SellerRegisteredNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;

class Navbar extends Component
{
    use Toast;

    public bool $isSeller = false;

    public bool $isAuctioner = false;

    public bool $sellerApplicationPending = false;

    public function mount(): void
    {
        if ($this->logoutIfInactive()) {
            return;
        }

        $this->syncSellerState();
    }

    public function requestSellerAccess(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if ($user->is_seller) {
            $this->info('Your account already has seller access.', position: 'toast-bottom');

            return;
        }

        if ($user->seller_application_pending) {
            $this->warning('Your seller request is already pending review.', position: 'toast-bottom');

            return;
        }

        $user->update([
            'seller_application_pending' => true,
        ]);

        Admin::query()->each(function (Admin $admin) use ($user): void {
            $admin->notify(new SellerRegisteredNotification($user));
        });

        $this->sellerApplicationPending = true;
        $this->success('Seller access request sent for admin review.', position: 'toast-bottom');
    }

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

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->success('Logged out successfully', position: 'toast-bottom');

        return $this->redirect(route('user.login'), navigate: true);
    }

    public function notificationData()
    {
        if (! Auth::check()) {
            return collect();
        }

        return Auth::user()
            ->notifications()
            ->latest()
            ->take(5)
            ->get();
    }

    public function handleNotificationClick($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if (! $notification) {
            return;
        }

        $notification->markAsRead();

        // Specific redirections for user
        $type = $notification->type;
        if ($type === 'App\Notifications\SellerApprovedNotification') {
            return $this->redirect(route('user.products'), navigate: true);
        }
        if ($type === 'App\Notifications\ProductApprovedNotification') {
            return $this->redirect(route('user.products'), navigate: true);
        }
        if ($type === 'App\Notifications\SellerSuspendedNotification') {
            return $this->redirect(route('home'), navigate: true);
        }
        if (
            $type === 'App\Notifications\AuctionApplicationApprovedNotification'
            || $type === 'App\Notifications\AuctionApplicationRejectedNotification'
        ) {
            return $this->redirect(route('user.join-auction'), navigate: true);
        }
        if ($type === 'App\Notifications\AccountStatusChangedNotification') {
            return $this->redirect(route('home'), navigate: true);
        }

        return $this->redirect(Auth::user()?->is_seller ? route('dashboard') : route('home'), navigate: true);
    }

    public function markAllAsRead(): void
    {
        Auth::user()
            ?->unreadNotifications()
            ->update(['read_at' => now()]);

        $this->success('All notifications marked as read.', position: 'toast-bottom');
    }

    private function syncSellerState(): void
    {
        $user = Auth::user();

        $this->isSeller = (bool) $user?->is_seller;
        $this->isAuctioner = (bool) $user?->is_auction_allowed;
        $this->sellerApplicationPending = (bool) $user?->seller_application_pending;
    }

    private function logoutIfInactive(): bool
    {
        $user = Auth::user();

        if (! $user || $user->isActiveStatus()) {
            return false;
        }

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('inactive_user_error', 'Your account has been marked inactive by the admin.');

        $this->redirect(route('user.login'), navigate: true);

        return true;
    }

    public function render()
    {
        if ($this->logoutIfInactive()) {
            return view('livewire.components.user.navbar', [
                'notifications' => collect(),
            ]);
        }

        $this->syncSellerState();

        return view('livewire.components.user.navbar', [
            'notifications' => $this->notificationData(),
        ]);
    }
}
