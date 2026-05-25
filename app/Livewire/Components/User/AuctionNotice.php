<?php

namespace App\Livewire\Components\User;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AuctionNotice extends Component
{
    public bool $is_auction_allowed = false;
    public bool $hide_notice = false;
    public $is_logged_in = false;

    public function mount()
    {
        $user = Auth::guard('web')->user();

        if ($user) {
            $this->is_auction_allowed = (bool)$user->is_auction_allowed;
            $this->is_logged_in = true;
        }

        $this->hide_notice = session('hide_auction_notice', false);
    }

    public function hide()
    {
        session(['hide_auction_notice' => true]);
        $this->hide_notice = true;
    }

    public function render()
    {
        return view('livewire.components.user.auction-notice');
    }
}
