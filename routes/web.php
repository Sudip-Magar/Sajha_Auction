<?php

use App\Http\Controllers\GoogleAuthController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Auth\Admin\Login as AdminLogin;
use App\Livewire\Auth\User\CompleteProfile;
use App\Livewire\Auth\User\Login;
use App\Livewire\Auth\User\Register;
use App\Livewire\Auth\User\VerifyOtp;
use App\Livewire\User\Dashboard;
use App\Livewire\Admin\CategorySetup;
use Illuminate\Support\Facades\Route;

Route::get('/register', Register::class)->name('user.register');
Route::get('/login', Login::class)->name('user.login');

Route::prefix('admin')->group(function () {
    Route::get('/login', AdminLogin::class)->name('admin.login');
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware('auth.otp')->group(function () {
    Route::get('/verify-otp', VerifyOtp::class)->name('verify.otp');
    Route::get('/complete-profile', CompleteProfile::class)->name('complete.profile');
});

Route::middleware('user')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
});

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('admin.dashboard');
    Route::get('/category-setup', CategorySetup::class)->name('admin.category-setup');
});
