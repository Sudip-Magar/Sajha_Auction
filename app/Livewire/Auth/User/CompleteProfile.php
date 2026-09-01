<?php

namespace App\Livewire\Auth\User;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CompleteProfile extends Component
{
    use WithFileUploads;

    // Pre-filled from Google
    public string $name = '';
    public string $email = '';
    public ?string $avatar = null;

    // User fills these in
    public string $username = '';
    public string $phone = '';
    public string $date_of_birth = '';
    public string $gender = '';
    public ?string $bio = null;

    public function mount(): void
    {
        $googleUser = session('google_user');
        $verified = session('otp_verified');

        // Guard: must have google session AND otp verified
        if (!$googleUser || !$verified) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        // Pre-fill from Google data
        $this->name = $googleUser['name'];
        $this->email = $googleUser['email'];
        $this->avatar = $googleUser['avatar'];
        $this->phone = $googleUser['phone'] ?? '';

        // Auto-fill username suggestion from name
        $this->username = str($googleUser['name'])->lower()->replace(' ', '_')->toString();
    }

    public function register(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,non_binary,prefer_not_to_say',
            'bio' => 'nullable|string|max:500',
        ]);

        $googleUser = session('google_user');

        $user = User::updateOrCreate(
            ['email' => $this->email],
            [
                'name' => $this->name,
                'username' => $this->username,
                'google_id' => $googleUser['google_id'],
                'avatar' => $this->avatar,
                'phone' => $this->phone ?: null,
                'date_of_birth' => $this->date_of_birth ?: null,
                'gender' => $this->gender ?: null,
                'bio' => $this->bio ?: null,
                'is_verified' => true,
                'password' => bcrypt(\Str::random(32)),
            ]
        );

        // Clean up all auth session data
        session()->forget(['google_user', 'otp_verified']);

        Auth::login($user, remember: true);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.user.complete-profile');
    }
}
