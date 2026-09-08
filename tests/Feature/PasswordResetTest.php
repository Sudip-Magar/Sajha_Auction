<?php

use App\Livewire\Auth\User\ForgotPassword;
use App\Livewire\Auth\User\ResetPassword;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('requesting a reset code emails an OTP and stores it in the session', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'reset-me@example.com']);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'reset-me@example.com')
        ->call('sendCode')
        ->assertRedirect(route('password.reset'));

    Mail::assertSent(PasswordResetOtpMail::class, fn ($mail) => $mail->hasTo($user->email));
    expect(session('password_reset.email'))->toBe($user->email);
});

test('requesting a reset code for an unknown email shows an error and sends nothing', function () {
    Mail::fake();

    Livewire::test(ForgotPassword::class)
        ->set('email', 'nobody@example.com')
        ->call('sendCode');

    Mail::assertNothingSent();
    expect(session('password_reset'))->toBeNull();
});

test('requesting a reset code for an inactive account shows an error and sends nothing', function () {
    Mail::fake();
    User::factory()->create(['email' => 'inactive@example.com', 'status' => 'inactive']);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'inactive@example.com')
        ->call('sendCode');

    Mail::assertNothingSent();
    expect(session('password_reset'))->toBeNull();
});

test('a correct code resets the password', function () {
    $user = User::factory()->create(['password' => 'old-password-123']);

    session([
        'password_reset' => [
            'email' => $user->email,
            'otp_code' => bcrypt('123456'),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_sent_at' => time(),
        ],
    ]);

    Livewire::test(ResetPassword::class)
        ->set('otp', '123456')
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword')
        ->assertRedirect(route('user.login'));

    expect(Hash::check('brand-new-password', $user->fresh()->password))->toBeTrue();
    expect(session('password_reset'))->toBeNull();
});

test('an incorrect code does not reset the password', function () {
    $user = User::factory()->create(['password' => 'old-password-123']);

    session([
        'password_reset' => [
            'email' => $user->email,
            'otp_code' => bcrypt('123456'),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_sent_at' => time(),
        ],
    ]);

    Livewire::test(ResetPassword::class)
        ->set('otp', '999999')
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword');

    expect(Hash::check('old-password-123', $user->fresh()->password))->toBeTrue();
});

test('an expired code does not reset the password', function () {
    $user = User::factory()->create(['password' => 'old-password-123']);

    session([
        'password_reset' => [
            'email' => $user->email,
            'otp_code' => bcrypt('123456'),
            'otp_expires_at' => now()->subMinute(),
            'otp_sent_at' => time() - 700,
        ],
    ]);

    Livewire::test(ResetPassword::class)
        ->set('otp', '123456')
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword');

    expect(Hash::check('old-password-123', $user->fresh()->password))->toBeTrue();
});

test('visiting reset-password without a pending request redirects to forgot-password', function () {
    Livewire::test(ResetPassword::class)
        ->assertRedirect(route('password.forgot'));
});
