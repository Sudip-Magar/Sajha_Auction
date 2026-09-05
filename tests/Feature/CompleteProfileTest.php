<?php

use App\Livewire\Auth\User\CompleteProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('requires users to be at least sixteen years old to complete registration', function () {
    session([
        'google_user' => [
            'name' => 'Young User',
            'email' => 'young@example.com',
        ],
        'otp_verified' => true,
    ]);

    Livewire::test(CompleteProfile::class)
        ->set('name', 'Young User')
        ->set('username', 'young_user')
        ->set('email', 'young@example.com')
        ->set('phone', '9812345678')
        ->set('date_of_birth_np', '2067-01-01')
        ->set('date_of_birth_en', today()->subYears(16)->addDay()->toDateString())
        ->set('gender', 'OTHER')
        ->set('password', 'Password1!')
        ->set('confirm_password', 'Password1!')
        ->set('agree_terms', true)
        ->call('register')
        ->assertHasErrors(['date_of_birth_en' => 'before_or_equal']);
});
