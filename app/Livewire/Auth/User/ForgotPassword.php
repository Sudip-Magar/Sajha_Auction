<?php

namespace App\Livewire\Auth\User;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Mary\Traits\Toast;

class ForgotPassword extends Component
{
    use Toast;

    public string $email = '';

    public function mount(): void
    {
        if (Auth::guard('web')->check()) {
            $this->redirect(route('home'), navigate: true);
        }
    }

    public function sendCode(): void
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $this->email)->first();

        if (! $user) {
            $this->error('No account found with that email address.', position: 'toast-bottom');

            return;
        }

        if (! $user->isActiveStatus()) {
            $this->error('Your account is inactive. Please contact the admin.', position: 'toast-bottom');

            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session([
            'password_reset' => [
                'email' => $user->email,
                'otp_code' => bcrypt($code),
                'otp_expires_at' => now()->addMinutes(10),
                'otp_sent_at' => time(),
            ],
        ]);

        Mail::to($user->email)->send(new PasswordResetOtpMail($code, $user->name));

        $this->success("A reset code has been sent to {$user->email}.", position: 'toast-bottom');

        $this->redirect(route('password.reset'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.user.forgot-password');
    }
}
