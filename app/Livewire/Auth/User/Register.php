<?php

namespace App\Livewire\Auth\User;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.user-auth')]
class Register extends Component
{
    public function render()
    {
        return view('livewire.auth.user.register');
    }
}
