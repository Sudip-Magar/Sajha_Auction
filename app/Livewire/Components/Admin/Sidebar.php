<?php

namespace App\Livewire\Components\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Sidebar extends Component
{
    public function logout()
    {
        Auth::guard('admin')->logout();
        session()->forget('admin_user');
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirect(route('admin.login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.components.admin.sidebar');
    }
}
