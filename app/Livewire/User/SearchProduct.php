<?php

namespace App\Livewire\User;

use Livewire\Component;

class SearchProduct extends Component
{
    public $search = '';
    public $category = '';

    public function mount(){
         $this->search = request('search');
         $this->category = request('category');
    }

    public function fetchData(){

    }
    public function render()
    {
        return view('livewire.user.search-product');
    }
}
