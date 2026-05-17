<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class Notifications extends Component
{
    use Toast, WithPagination;

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

    public function markAsRead(string $notificationId): void
    {
        /** @var DatabaseNotification|null $notification */
        $notification = Auth::guard('admin')
            ->user()
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
        Auth::guard('admin')
            ->user()
            ?->unreadNotifications()
            ->update(['read_at' => now()]);

        $this->success('All notifications marked as read.');
    }

    public function notificationRoute(DatabaseNotification $notification): string
    {
        return match ($notification->type) {
            'App\Notifications\SellerRegisteredNotification' => route('admin.seller-requests'),
            'App\Notifications\NewProductUploadedNotification' => route('admin.products'),
            default => route('admin.dashboard'),
        };
    }

    public function render(): View
    {
        return view('livewire.admin.notifications', [
            'notifications' => Auth::guard('admin')->user()->notifications()->latest()->paginate(15),
            'unreadCount' => Auth::guard('admin')->user()->unreadNotifications()->count(),
        ]);
    }
}
