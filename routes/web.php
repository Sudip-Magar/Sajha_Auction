<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\User\CompleteProfile;
use App\Livewire\Auth\User\VerifyOtp;
use App\Http\Controllers\GoogleAuthController;
use App\Livewire\Auth\User\Register;
use App\Livewire\Auth\User\Login;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', Register::class)->name('user.register');
Route::get('/login', Login::class)->name('user.login');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware('auth.otp')->group(function () {
    Route::get('/verify-otp',VerifyOtp::class)->name('verify.otp');
    Route::get('/complete-profile', CompleteProfile::class)->name('complete.profile');
});
