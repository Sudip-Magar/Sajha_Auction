<?php

namespace App\Livewire\Auth\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.admin-auth')]
class Login extends Component
{
    use Toast;

    public $email;

    public $password;

    public function mount()
    {
        if (Auth::guard('admin')->check()) {
            $this->redirect(route('admin.dashboard'), navigate: true);
        }
    }

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = 'admin-login:'.strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->error('Too many sign-in attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.', position: 'toast-bottom');

            return;
        }

        if (! Auth::guard('admin')->attempt(['email' => $this->email, 'password' => $this->password, 'status' => 'active'], remember: true)) {
            RateLimiter::hit($throttleKey, 60);
            $this->error('Invalid credentials. Please check your email and password.', position: 'toast-bottom');

            return;
        }

        RateLimiter::clear($throttleKey);

        session([
            'admin_user' => [
                'id' => Auth::guard('admin')->user()->id,
                'name' => Auth::guard('admin')->user()->name,
                'email' => Auth::guard('admin')->user()->email,
                'avatar' => Auth::guard('admin')->user()->avatar,
            ],
        ]);

        $this->success('Welcome back! You have logged in successfully.', position: 'toast-bottom');

        return redirect()->route('admin.dashboard');
    }

    public function render()
    {
        return view('livewire.auth.admin.login');
    }
}
