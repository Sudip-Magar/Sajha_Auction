<?php

namespace App\Livewire\Auth\User;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

// #[Layout('layouts.auth')]
class Login extends Component
{
    use Toast;

    public $email = '';

    public string $password = '';

    public function mount()
    {
        if (Auth::guard('web')->check()) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], remember: true)) {
            $this->error('Invalid credentials. Please check your email and password.', position: 'toast-bottom');

            return;
        }

        session([
            'google_user' => [
                'google_id' => null,
                'name' => null,
                'email' => $this->email,
                'avatar' => null,
                'phone' => null,
            ],
            'otp_verified' => true,
        ]);

        $this->success('Welcome back! You have logged in successfully.', position: 'toast-bottom');

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.user.login');
    }
}
