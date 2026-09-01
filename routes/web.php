<?php

use App\Http\Controllers\GoogleAuthController;
use App\Livewire\Auth\User\Register;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', Register::class)->name('user.register');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::middleware('auth.otp')->group(function () {
    Route::get('/verify-otp', fn() => view('pages.verify-otp'))->name('verify.otp');
    Route::get('/complete-profile', fn() => view('pages.complete-profile'))->name('complete.profile');
});
