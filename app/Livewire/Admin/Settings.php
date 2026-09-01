<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class Settings extends Component
{
    use Toast;

    public string $site_name = '';

    public string $site_email = '';

    public string $contact_phone = '';

    public string $address = '';

    public bool $maintenance_mode = false;

    public string $facebook_url = '';

    public string $twitter_url = '';

    public string $instagram_url = '';

    public int $session_timeout = 30;

    public int $password_min_length = 8;

    public bool $require_strong_password = true;

    public bool $two_factor_required = false;

    public string $activeTab = 'general';

    public function mount()
    {
        $this->site_name = Setting::get('site_name', config('app.name'));
        $this->site_email = Setting::get('site_email', 'admin@sajhaauction.com');
        $this->contact_phone = Setting::get('contact_phone', '');
        $this->address = Setting::get('address', '');
        $this->maintenance_mode = (bool) Setting::get('maintenance_mode', false);

        $this->facebook_url = Setting::get('facebook_url', '');
        $this->twitter_url = Setting::get('twitter_url', '');
        $this->instagram_url = Setting::get('instagram_url', '');

        $this->session_timeout = (int) Setting::get('session_timeout', 30);
        $this->password_min_length = (int) Setting::get('password_min_length', 8);
        $this->require_strong_password = (bool) Setting::get('require_strong_password', true);
        $this->two_factor_required = (bool) Setting::get('two_factor_required', false);
    }

    public function saveGeneral()
    {
        Setting::set('site_name', $this->site_name);
        Setting::set('site_email', $this->site_email);
        Setting::set('contact_phone', $this->contact_phone);
        Setting::set('address', $this->address);
        Setting::set('maintenance_mode', $this->maintenance_mode);

        $this->success('General settings updated successfully.');
    }

    public function saveSocial()
    {
        Setting::set('facebook_url', $this->facebook_url);
        Setting::set('twitter_url', $this->twitter_url);
        Setting::set('instagram_url', $this->instagram_url);

        $this->success('Social settings updated successfully.');
    }

    public function saveSecurity(): void
    {
        $this->validate([
            'session_timeout' => 'required|integer|min:5|max:240',
            'password_min_length' => 'required|integer|min:8|max:32',
            'require_strong_password' => 'boolean',
            'two_factor_required' => 'boolean',
        ]);

        Setting::set('session_timeout', $this->session_timeout, 'security');
        Setting::set('password_min_length', $this->password_min_length, 'security');
        Setting::set('require_strong_password', $this->require_strong_password, 'security');
        Setting::set('two_factor_required', $this->two_factor_required, 'security');

        $this->success('Security settings updated successfully.');
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}
