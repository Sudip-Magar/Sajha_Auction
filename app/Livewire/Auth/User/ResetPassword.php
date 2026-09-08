<?php

namespace App\Livewire\Auth\User;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Mary\Traits\Toast;

class ResetPassword extends Component
{
    use Toast;

    public string $otp = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $email = '';

    public int $countdown = 30;

    public function mount(): void
    {
        if (Auth::guard('web')->check()) {
            $this->redirect(route('home'), navigate: true);

            return;
        }

        $state = session('password_reset');

        if (! $state) {
            $this->redirect(route('password.forgot'), navigate: true);

            return;
        }

        $this->email = $state['email'];

        $sentAt = $state['otp_sent_at'] ?? 0;
        $elapsed = time() - $sentAt;
        $this->countdown = $elapsed >= 30 ? 0 : 30 - $elapsed;
    }

    public function resendCode(): void
    {
        $state = session('password_reset');

        if (! $state) {
            $this->redirect(route('password.forgot'), navigate: true);

            return;
        }

        $sentAt = $state['otp_sent_at'] ?? 0;
        if ((time() - $sentAt) < 30) {
            return;
        }

        $user = User::where('email', $state['email'])->first();

        if (! $user) {
            $this->redirect(route('password.forgot'), navigate: true);

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

        $this->countdown = 30;
        $this->success('A new code has been sent.', position: 'toast-bottom');
        $this->dispatch('start-countdown');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'otp' => 'required|digits:6',
            'password' => 'required|min:8|confirmed',
        ]);

        $state = session('password_reset');

        if (! $state) {
            $this->error('Session expired. Please start again.', position: 'toast-bottom');
            $this->redirect(route('password.forgot'), navigate: true);

            return;
        }

        if (now()->isAfter($state['otp_expires_at'])) {
            $this->error('This code has expired. Please request a new one.', position: 'toast-bottom');

            return;
        }

        if (! Hash::check($this->otp, $state['otp_code'])) {
            $this->error('Incorrect code. Please try again.', position: 'toast-bottom');

            return;
        }

        $user = User::where('email', $state['email'])->first();

        if (! $user) {
            $this->error('Account not found.', position: 'toast-bottom');
            $this->redirect(route('password.forgot'), navigate: true);

            return;
        }

        $user->update(['password' => $this->password]);

        session()->forget('password_reset');

        $this->success('Password reset. Please sign in with your new password.', position: 'toast-bottom');
        $this->redirect(route('user.login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.user.reset-password');
    }
}
