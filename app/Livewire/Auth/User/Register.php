<?php

namespace App\Livewire\Auth\User;

use Laravel\Socialite\Facades\Socialite;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class Register extends Component
{
    public function googleAuth()
    {
        return redirect()->route('auth.google.redirect');
    }

    public function callback()
    {
        try {
            $user = Socialite::driver('google')->user();
            dd($user);
        } catch (\Exception $e) {
            return $this->redirect('/')->with('error', $e->getMessage());

        }
    }

    public function render()
    {
        return view('livewire.auth.user.register');
    }
}
