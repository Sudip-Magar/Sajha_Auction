<?php

use App\Enums\DocumentImageType;
use App\Livewire\Admin\UserDetail;
use App\Livewire\Auth\User\Login;
use App\Models\Admin;
use App\Models\User;
use App\Notifications\AuctionApplicationApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('blocks inactive users from logging in', function () {
    $user = User::factory()->create([
        'password' => 'password',
        'status' => 'inactive',
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertNoRedirect();

    expect(auth()->check())->toBeFalse();
});

it('logs inactive users out through the user middleware', function () {
    $user = User::factory()->create([
        'status' => 'inactive',
    ]);

    $this->actingAs($user)
        ->get(route('user.settings'))
        ->assertRedirect(route('user.login'));

    expect(auth()->check())->toBeFalse();
});

it('allows admin to manage user status seller access and auction access from detail page', function () {
    Notification::fake();

    $admin = Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'phone' => '9800000000',
        'password' => 'password',
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'status' => 'active',
        'is_seller' => true,
        'is_auction_allowed' => false,
    ]);

    $user->documentImages()->create([
        'type' => DocumentImageType::PASSPORT->value,
        'image' => 'document-images/passport.png',
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(UserDetail::class, ['user' => $user])
        ->call('toggleStatus')
        ->call('toggleSellerAccess')
        ->call('toggleAuctionAccess');

    $user->refresh();

    expect($user->status)->toBe('inactive')
        ->and($user->is_seller)->toBeFalse()
        ->and($user->is_auction_allowed)->toBeTrue();

    Notification::assertSentTo($user, AuctionApplicationApprovedNotification::class);
});
