<?php

namespace App\Livewire\Components\User;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;

class Navbar extends Component
{
    use Toast;

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->success('Logged out successfully', position: 'toast-bottom');

        return $this->redirect(route('user.login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.components.user.navbar');
    }
}
