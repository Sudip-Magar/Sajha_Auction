<?php

namespace App\Livewire\Auth\User;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            $this->redirect(route('home'), navigate: true);
        }

        if (session()->has('inactive_user_error')) {
            $this->error(session('inactive_user_error'), position: 'toast-bottom');
        }
    }

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $this->email)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            $this->error('Invalid credentials. Please check your email and password.', position: 'toast-bottom');

            return;
        }

        if (! $user->isActiveStatus()) {
            $this->error('Your account is inactive. Please contact the admin.', position: 'toast-bottom');

            return;
        }

        Auth::login($user, remember: true);

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

        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.user.login');
    }
}
