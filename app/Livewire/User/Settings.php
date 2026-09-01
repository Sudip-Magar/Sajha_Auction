<?php

namespace App\Livewire\User;

use App\Models\Admin;
use App\Notifications\SellerRegisteredNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Settings extends Component
{
    use Toast, WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $bio = '';

    public $avatar;

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public string $activeTab = 'profile';

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->bio = $user->bio ?? '';
    }

    public function updateProfile()
    {
        $user = Auth::user();

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|max:1024',
        ]);

        if ($this->avatar) {
            $data['avatar'] = $this->avatar->store('avatars', 'public');
        }

        $user->update($data);
        $this->success('Profile updated successfully.');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|min:8|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->success('Password changed successfully.');
    }

    public function requestSellerAccess(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if ($user->is_seller) {
            $this->info('Your account already has seller access.');

            return;
        }

        if ($user->seller_application_pending) {
            $this->warning('Your seller request is already pending review.');

            return;
        }

        $user->update([
            'seller_application_pending' => true,
        ]);

        $admins = Admin::all();
        foreach ($admins as $admin) {
            $admin->notify(new SellerRegisteredNotification($user));
        }

        $this->success('Seller access request sent for admin review.');
    }

    public function render()
    {
        return view('livewire.user.settings');
    }
}
