<?php

namespace App\Livewire\Components\User;

use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Footer extends Component
{
    public function render(): View
    {
        return view('livewire.components.user.footer', [
            'socialLinks' => [
                'facebook' => Setting::get('facebook_url', ''),
                'twitter' => Setting::get('twitter_url', ''),
                'instagram' => Setting::get('instagram_url', ''),
            ],
        ]);
    }
}
