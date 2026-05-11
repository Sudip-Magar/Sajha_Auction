<?php

namespace App\Livewire\Auth\Admin;

use Illuminate\Support\Facades\Auth;
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

        if (! Auth::guard('admin')->attempt(['email' => $this->email, 'password' => $this->password], remember: true)) {
            $this->error('Invalid credentials. Please check your email and password.', position: 'toast-bottom');

            return;
        }

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
