<?php

namespace App\Livewire\Components\User;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;

class Navbar extends Component
{
    use Toast;

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

        return $this->redirect(route('dashboard'), navigate: true);
    }

    public function markAllAsRead(): void
    {
        Auth::user()
            ?->unreadNotifications()
            ->update(['read_at' => now()]);

        $this->success('All notifications marked as read.', position: 'toast-bottom');
    }

    public function render()
    {
        return view('livewire.components.user.navbar', [
            'notifications' => $this->notificationData(),
        ]);
    }
}
