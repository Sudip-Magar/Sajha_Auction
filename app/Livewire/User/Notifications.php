<?php

namespace App\Livewire\User;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Notifications extends Component
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

    public function markAsRead(string $notificationId): void
    {
        /** @var DatabaseNotification|null $notification */
        $notification = Auth::user()
            ?->notifications()
            ->find($notificationId);

        if (! $notification) {
            return;
        }

        $notification->markAsRead();
        $this->success('Notification marked as read.');
    }

    public function markAllAsRead(): void
    {
        Auth::user()
            ?->unreadNotifications()
            ->update(['read_at' => now()]);

        $this->success('All notifications marked as read.');
    }

    public function notificationRoute(DatabaseNotification $notification): string
    {
        return match ($notification->type) {
            'App\Notifications\SellerApprovedNotification',
            'App\Notifications\ProductApprovedNotification' => route('user.products'),
            default => Auth::user()?->is_seller ? route('dashboard') : route('home'),
        };
    }

    public function render(): View
    {
        return view('livewire.user.notifications', [
            'notifications' => Auth::user()->notifications()->latest()->paginate(15),
            'unreadCount' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
