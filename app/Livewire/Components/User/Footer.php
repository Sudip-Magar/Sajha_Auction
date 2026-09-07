<?php

namespace App\Livewire\Components\User;

use App\Models\Setting;
use App\Models\Subscriber;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Mary\Traits\Toast;

class Footer extends Component
{
    use Toast;

    public string $newsletterEmail = '';

    public function subscribe(): void
    {
        $this->validate([
            'newsletterEmail' => 'required|email',
        ]);

        if (Subscriber::where('email', $this->newsletterEmail)->exists()) {
            $this->warning('This email is already subscribed.');

            return;
        }

        Subscriber::create(['email' => $this->newsletterEmail]);

        $this->newsletterEmail = '';
        $this->success('Thanks for subscribing! You will hear from us soon.');
    }

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
