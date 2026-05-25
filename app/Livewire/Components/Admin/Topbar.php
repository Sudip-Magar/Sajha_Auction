<?php

namespace App\Livewire\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;

class Topbar extends Component
{
    use Toast;

    public function getListeners(): array
    {
        $adminId = Auth::guard('admin')->id();

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

    public function notificationData()
    {
        return Auth::guard('admin')
            ->user()
            ->notifications()
            ->latest()
            ->take(10)
            ->get();
    }

    public function handleNotificationClick(string $id)
    {
        $notification = Auth::guard('admin')->user()->notifications()->find($id);

        if (! $notification) {
            return;
        }

        // Mark as read
        $notification->markAsRead();

        // Direct Redirection based on type
        $type = $notification->type;

        if ($type === 'App\Notifications\SellerRegisteredNotification') {
            return $this->redirect(route('admin.seller-requests'), navigate: true);
        }

        if ($type === 'App\Notifications\NewProductUploadedNotification') {
            return $this->redirect(route('admin.products'), navigate: true);
        }

        if ($type === 'App\Notifications\AuctionApplicationSubmittedNotification') {
            $userId = $notification->data['user_id'] ?? null;

            if ($userId) {
                return $this->redirect(route('admin.auction-application.show', $userId), navigate: true);
            }

            return $this->redirect(route('admin.auction-application'), navigate: true);
        }

        // Fallback or other types can be added here
        return $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function markAllAsRead(): void
    {
        Auth::guard('admin')
            ->user()
            ?->unreadNotifications()
            ->update(['read_at' => now()]);

        $this->success('All notifications marked as read.');
    }

    public function render(): View
    {
        return view('livewire.components.admin.topbar', [
            'notifications' => $this->notificationData(),
        ]);
    }
}
