<?php

namespace App\Livewire\Components\User;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AuctionNotice extends Component
{
    public bool $is_auction_allowed = false;

    public bool $hide_notice = false;

    public bool $is_logged_in = false;

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

    public function mount(): void
    {
        $this->syncState();
        $this->hide_notice = session('hide_auction_notice', false);
    }

    public function hide(): void
    {
        session(['hide_auction_notice' => true]);
        $this->hide_notice = true;
    }

    public function render()
    {
        $this->syncState();

        return view('livewire.components.user.auction-notice');
    }

    private function syncState(): void
    {
        $user = Auth::guard('web')->user();

        $this->is_logged_in = (bool) $user;
        $this->is_auction_allowed = (bool) $user?->is_auction_allowed;
    }
}
