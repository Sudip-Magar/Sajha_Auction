<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class CategorySetup extends Component
{
    public function render()
    {
        return view('livewire.admin.category-setup');
    }
}
