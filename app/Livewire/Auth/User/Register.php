<?php

namespace App\Livewire\Auth\User;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

// #[Layout('layouts.auth')]
class Register extends Component
{
    public $email = '';
    // public bool $term = false;

    public function mount()
    {
        if (Auth::guard('web')->check()) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function verifyEmail()
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        session([
            'google_user' => [
                'google_id' => null,
                'name' => null,
                'email' => $this->email,
                'avatar' => null,
                'phone' => null,
            ],
            'otp_verified' => false,
        ]);

        return redirect()->route('verify.otp');
    }

    public function render()
    {
        return view('livewire.auth.user.register');
    }
}
