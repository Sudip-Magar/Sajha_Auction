<?php

namespace App\Livewire\Auth\User;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Mary\Traits\Toast;

class VerifyOtp extends Component
{
    use Toast;

    public string $otp = '';

    public string $email = '';

    public ?string $name = null;

    public bool $otpSent = false;

    public string $message = '';

    public bool $isError = false;

    public int $countdown = 60;

    public function mount(): void
    {
        if (Auth::guard('web')->check()) {
            $this->redirect(route('home'), navigate: true);
        }

        $googleUser = session('google_user');

        if (! $googleUser) {
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        $this->email = $googleUser['email'];
        $this->name = $googleUser['name'] ?? null;

        $lastSent = session('otp_sent_at');
        if ($lastSent && ! is_numeric($lastSent)) {
            $lastSent = strtotime($lastSent);
        }

        if (! $lastSent || (time() - $lastSent) >= 30) {
            $this->sendOtp();
        } else {
            $this->countdown = 30 - (time() - $lastSent);
        }
    }

    public function sendOtp(): void
    {
        $lastSent = session('otp_sent_at');
        if ($lastSent && ! is_numeric($lastSent)) {
            $lastSent = strtotime($lastSent);
        }

        if ($lastSent && (time() - $lastSent) < 30) {
            $this->setError('Please wait before resending.');

            return;
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session([
            'otp_code' => bcrypt($code),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_sent_at' => time(),
        ]);

        Mail::to($this->email)->send(new OtpMail($code, $this->name));

        $this->otpSent = true;
        $this->message = "A 6-digit code was sent to {$this->email}";
        $this->isError = false;
        $this->countdown = 30;

        $this->dispatch('start-countdown');
    }

    public function updatedOtp($value)
    {
        // Auto-verify when 6 digits are entered
        if (strlen($value) === 6) {
            $this->verifyOtp();
        }
    }

    public function verifyOtp(): void
    {
        $this->validate(['otp' => 'required|digits:6']);

        $storedOtp = session('otp_code');
        $expiry = session('otp_expires_at');
        $googleUser = session('google_user');

        if (! $storedOtp || ! $googleUser) {
            $this->setError('Session expired. Please start again.');

            return;
        }

        if (now()->isAfter($expiry)) {
            $this->setError('OTP has expired. Please request a new one.');

            return;
        }

        if (! \Hash::check($this->otp, $storedOtp)) {
            $this->setError('Incorrect OTP. Please try again.');

            return;
        }

        // Mark OTP as verified — unlock profile completion page
        session(['otp_verified' => true]);

        // Clear OTP from session
        session()->forget(['otp_code', 'otp_expires_at']);

        $existingUser = User::where('email', $this->email)->first();

        if (! $existingUser) {
            // New user — send them to complete their profile
            $this->redirect(route('complete.profile'), navigate: true);
        } else {
            // Existing user — log them in and clear the google session
            Auth::login($existingUser, remember: true);
            session()->forget(['google_user', 'otp_verified']);

            $this->success('Welcome back! You have logged in successfully.', position: 'toast-bottom');

            $this->redirect(route('home'), navigate: true);
        }

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
