<?php

use App\Http\Controllers\GoogleAuthController;
use App\Livewire\Admin\AuctionApplication;
use App\Livewire\Admin\AuctionApplicationDetail;
use App\Livewire\Admin\CategorySetup;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Notifications as AdminNotifications;
use App\Livewire\Admin\ProductDetail as AdminProductDetail;
use App\Livewire\Admin\SellerRequests;
use App\Livewire\Admin\UserDetail as AdminUserDetail;
use App\Livewire\Admin\Users as AdminUsers;
use App\Livewire\Auth\Admin\Login as AdminLogin;
use App\Livewire\Auth\User\CompleteProfile;
use App\Livewire\Auth\User\Login;
use App\Livewire\Auth\User\Register;
use App\Livewire\Auth\User\VerifyOtp;
use App\Livewire\Home;
use App\Livewire\User\Dashboard;
use App\Livewire\User\JoinAuction;
use App\Livewire\User\Notifications as UserNotifications;
use App\Livewire\User\Products;
use App\Livewire\User\Settings;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('home');
});

Route::get('/home', Home::class)->name('home');
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
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/notifications', UserNotifications::class)->name('user.notifications');
    Route::get('/settings', Settings::class)->name('user.settings');
    Route::get('/my-products', Products::class)->name('user.products');
    Route::get('/join-auction', JoinAuction::class)->name('user.join-auction');
});

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('admin.dashboard');
    Route::get('/users', AdminUsers::class)->name('admin.users');
    Route::get('/users/{user}', AdminUserDetail::class)->name('admin.users.show');
    Route::get('/category-setup', CategorySetup::class)->name('admin.category-setup');
    Route::get('/seller-requests', SellerRequests::class)->name('admin.seller-requests');
    Route::get('/products', App\Livewire\Admin\Products::class)->name('admin.products');
    Route::get('/products/{product}', AdminProductDetail::class)->name('admin.products.show');
    Route::get('/notifications', AdminNotifications::class)->name('admin.notifications');
    Route::get('/settings', App\Livewire\Admin\Settings::class)->name('admin.settings');
    Route::get('/auction-application', AuctionApplication::class)->name('admin.auction-application');
    Route::get('/auction-application/{user}', AuctionApplicationDetail::class)->name('admin.auction-application.show');
});
