<?php

namespace App\Livewire\Components\User;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Topbar extends Component
{
    public function mount(){
        $this->notificationData();
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

    public function render()
    {
        return view('livewire.components.user.topbar', [
            'notifications' => $this->notificationData(),
        ]);
    }
}
