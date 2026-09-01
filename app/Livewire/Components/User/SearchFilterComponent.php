<?php

namespace App\Livewire\Components\User;

use Livewire\Component;

class SearchFilterComponent extends Component
{
    public $search;
    public $category;

    public function mount()
    {
        $this->search = request('search');
        $this->category = request('category');
    }

    public function render()
    {
        return view('livewire.components.user.search-filter-component');
    }

}
