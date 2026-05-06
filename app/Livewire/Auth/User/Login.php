<?php

namespace App\Livewire\Auth\User;

use Livewire\Component;

class Login extends Component
{
    public $email = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        session([
            'google_user' => [
                'google_id' => null,
                'name'  => null,
                'email' => $this->email,
                'avatar' => null,
                'phone'  => null,
            ],
            'otp_verified' => false,
        ]);
        
        return redirect()->route('verify.otp');
    }

    public function render()
    {
        return view('livewire.auth.user.login');
    }
}
