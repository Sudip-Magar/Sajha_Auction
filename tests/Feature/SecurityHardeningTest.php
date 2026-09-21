<?php

use App\Livewire\Auth\Admin\Login as AdminLogin;
use App\Livewire\Auth\User\Login;
use App\Livewire\Auth\User\VerifyOtp;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeAdmin(string $status): Admin
{
    return Admin::create([
        'name' => 'Admin '.$status,
        'email' => $status.'@example.com',
        'phone' => '98'.random_int(10000000, 99999999),
        'password' => 'password',
        'status' => $status,
    ]);
}

test('an inactive admin cannot sign in', function () {
    makeAdmin('inactive');

    Livewire::test(AdminLogin::class)
        ->set('email', 'inactive@example.com')
        ->set('password', 'password')
        ->call('login');

    expect(Auth::guard('admin')->check())->toBeFalse();
});

test('an active admin can still sign in', function () {
    makeAdmin('active');

    Livewire::test(AdminLogin::class)
        ->set('email', 'active@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('admin')->check())->toBeTrue();
});

test('admin sign-in is locked after five wrong passwords, even for the right password', function () {
    makeAdmin('active');

    $component = Livewire::test(AdminLogin::class)->set('email', 'active@example.com');

    foreach (range(1, 5) as $ignored) {
        $component->set('password', 'wrong-password')->call('login');
    }

    $component->set('password', 'password')->call('login');

    expect(Auth::guard('admin')->check())->toBeFalse();
});

test('user sign-in is locked after five wrong passwords, even for the right password', function () {
    $user = User::factory()->create(['email' => 'locked@example.com', 'password' => 'Correct#Pass1']);

    $component = Livewire::test(Login::class)->set('email', $user->email);

    foreach (range(1, 5) as $ignored) {
        $component->set('password', 'wrong-password')->call('login');
    }

    $component->set('password', 'Correct#Pass1')->call('login');

    expect(Auth::guard('web')->check())->toBeFalse();
});

test('a correct password still signs the user in before the limit is reached', function () {
    $user = User::factory()->create(['email' => 'ok@example.com', 'password' => 'Correct#Pass1']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')->call('login')
        ->set('password', 'Correct#Pass1')->call('login');

    expect(Auth::guard('web')->check())->toBeTrue();
});

test('five wrong sign-up codes discard the code so it cannot be brute-forced', function () {
    Mail::fake();

    session([
        'google_user' => ['google_id' => null, 'name' => null, 'email' => 'new@example.com', 'avatar' => null, 'phone' => null],
        'otp_verified' => false,
        'otp_code' => Hash::make('123456'),
        'otp_expires_at' => now()->addMinutes(10),
        'otp_sent_at' => time(),
    ]);

    $component = Livewire::test(VerifyOtp::class);

    foreach (range(1, 5) as $ignored) {
        $component->set('otp', '000000')->call('verifyOtp');
    }

    // Even the correct code is now refused, and the stored code is gone.
    $component->set('otp', '123456')->call('verifyOtp');

    expect(session('otp_verified'))->toBeFalse()
        ->and(session('otp_code'))->toBeNull();
});

test('a cancelled order no longer accepts a deposit payment', function () {
    $order = new Order(['deposit_status' => 'pending', 'status' => 'pending']);
    expect($order->needsDeposit())->toBeTrue();

    $order->status = 'cancelled';
    expect($order->needsDeposit())->toBeFalse();
});
