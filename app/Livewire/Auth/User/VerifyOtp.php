<?php

namespace App\Livewire\Auth\User;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class VerifyOtp extends Component
{
    public string $otp = '';
    public string $email = '';
    public string $name = '';
    public bool $otpSent = false;
    public string $message = '';
    public bool $isError = false;
    public int $countdown = 60;

    public function mount()
    {
        $googleUser = session('google_user');

        if (!$googleUser) {
            $this->redirect(route('user.login'), navigate: true);
            return;
        }

        $this->email = $googleUser['email'];
        $this->name = $googleUser['name'];

        // Auto-send OTP on page load
        $this->sendOtp();
    }

    public function sendOtp(): void
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session([
            'otp_code' => bcrypt($code),
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($this->email)->send(new OtpMail($code, $this->name));

        $this->otpSent = true;
        $this->message = "A 6-digit code was sent to {$this->email}";
        $this->isError = false;
        $this->countdown = 60;

        $this->dispatch('start-countdown');
    }

    public function verifyOtp(): void
    {
        $this->validate(['otp' => 'required|digits:6']);

        $storedOtp = session('otp_code');
        $expiry = session('otp_expires_at');
        $googleUser = session('google_user');

        if (!$storedOtp || !$googleUser) {
            $this->setError('Session expired. Please start again.');
            return;
        }

        if (now()->isAfter($expiry)) {
            $this->setError('OTP has expired. Please request a new one.');
            return;
        }

        if (!\Hash::check($this->otp, $storedOtp)) {
            $this->setError('Incorrect OTP. Please try again.');
            return;
        }

        // Mark OTP as verified — unlock profile completion page
        session(['otp_verified' => true]);

        // Clear OTP from session
        session()->forget(['otp_code', 'otp_expires_at']);

        // Redirect to profile completion
        $this->redirect(route('complete.profile'), navigate: true);
    }

    private function setError(string $msg): void
    {
        $this->message = $msg;
        $this->isError = true;
    }

    public function render()
    {
        return view('livewire.auth.user.verify-otp');
    }
}
