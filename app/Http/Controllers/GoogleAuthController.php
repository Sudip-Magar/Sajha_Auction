<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['profile', 'email'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $guzzleClient = new \GuzzleHttp\Client(['verify' => false]);
            
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver('google');
            
            $googleUser = $driver->setHttpClient($guzzleClient)->user();
        } catch (\Exception $e) {
            \Log::error('Google Auth Error: ' . $e->getMessage());
            return redirect()->route('user.login')->with('error', 'Google authentication failed.');
        }

        // Store raw google data in session — Livewire takes it from here
        session([
            'google_user' => [
                'google_id' => $googleUser->getId() ?? '',
                'name' => $googleUser->getName() ?? '',
                'email' => $googleUser->getEmail() ?? '',
                'avatar' => $googleUser->getAvatar() ?? '',
                // Google OAuth doesn't return phone by default; store null
                'phone' => $googleUser->user['phone_number'] ?? null,
            ],
            'otp_verified' => false,
        ]);

        return redirect()->route('verify.otp');
    }

}
