<?php

use App\Enums\DocumentImageType;
use App\Livewire\Admin\AuctionApplication;
use App\Livewire\Admin\AuctionApplicationDetail;
use App\Livewire\User\JoinAuction;
use App\Models\Admin;
use App\Models\User;
use App\Notifications\AuctionApplicationSubmittedNotification;
use App\Notifications\SellerRegisteredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeReviewingAdmin(): Admin
{
    return Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin-'.Str::random(5).'@example.com',
        'phone' => '9800000000',
        'password' => 'password',
        'status' => 'active',
    ]);
}

test('a non-seller who submits a join-auction application also gets a bundled seller request', function () {
    Storage::fake('public');
    Notification::fake();

    $admin = makeReviewingAdmin();
    $user = User::factory()->create(['is_seller' => false, 'seller_application_pending' => false]);

    Livewire::actingAs($user)->test(JoinAuction::class)
        ->set('documentRows', [[
            'id' => null,
            'type' => DocumentImageType::PASSPORT->value,
            'image_path' => null,
            'image' => null,
        ]])
        ->set('documentRows.0.image', UploadedFile::fake()->image('passport.png'))
        ->call('submitApplication')
        ->assertHasNoErrors();

    expect($user->fresh()->seller_application_pending)->toBeTrue();

    Notification::assertSentTo($admin, AuctionApplicationSubmittedNotification::class);
    Notification::assertSentTo($admin, SellerRegisteredNotification::class);
});

test('a non-seller does not get a second bundled seller request if one is already pending', function () {
    Storage::fake('public');
    Notification::fake();

    makeReviewingAdmin();
    $user = User::factory()->create(['is_seller' => false, 'seller_application_pending' => true]);

    Livewire::actingAs($user)->test(JoinAuction::class)
        ->set('documentRows', [[
            'id' => null,
            'type' => DocumentImageType::PASSPORT->value,
            'image_path' => null,
            'image' => null,
        ]])
        ->set('documentRows.0.image', UploadedFile::fake()->image('passport.png'))
        ->call('submitApplication')
        ->assertHasNoErrors();

    Notification::assertNotSentTo(Admin::first(), SellerRegisteredNotification::class);
});

test('a seller who submits a join-auction application does not trigger a seller request', function () {
    Storage::fake('public');
    Notification::fake();

    makeReviewingAdmin();
    $user = User::factory()->create(['is_seller' => true]);

    Livewire::actingAs($user)->test(JoinAuction::class)
        ->set('documentRows', [[
            'id' => null,
            'type' => DocumentImageType::PASSPORT->value,
            'image_path' => null,
            'image' => null,
        ]])
        ->set('documentRows.0.image', UploadedFile::fake()->image('passport.png'))
        ->call('submitApplication')
        ->assertHasNoErrors();

    expect($user->fresh()->seller_application_pending)->toBeFalse();

    Notification::assertNotSentTo(Admin::first(), SellerRegisteredNotification::class);
});

test('the join-auction form shows a locked-on "also apply as seller" toggle only for non-sellers', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    Livewire::actingAs($seller)->test(JoinAuction::class)
        ->assertDontSee('Also apply as seller');

    $nonSeller = User::factory()->create(['is_seller' => false]);
    Livewire::actingAs($nonSeller)->test(JoinAuction::class)
        ->assertSee('Also apply as seller')
        ->assertSet('requestSellerAccessToo', true)
        ->set('requestSellerAccessToo', false)
        ->assertSet('requestSellerAccessToo', true);
});

test('admin cannot approve an auction application for a user who is not yet a seller', function () {
    Notification::fake();
    $admin = makeReviewingAdmin();
    $user = User::factory()->create(['is_seller' => false, 'is_auction_allowed' => false]);
    $user->documentImages()->create([
        'type' => DocumentImageType::PASSPORT->value,
        'image' => 'document-images/passport.png',
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(AuctionApplication::class)
        ->call('approveApplication', $user);

    expect($user->fresh()->is_auction_allowed)->toBeFalse();

    Livewire::actingAs($admin, 'admin')
        ->test(AuctionApplicationDetail::class, ['user' => $user])
        ->call('approveApplication');

    expect($user->fresh()->is_auction_allowed)->toBeFalse();
});

test('admin can approve an auction application once the user has seller access', function () {
    Notification::fake();
    $admin = makeReviewingAdmin();
    $user = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => false]);
    $user->documentImages()->create([
        'type' => DocumentImageType::PASSPORT->value,
        'image' => 'document-images/passport.png',
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(AuctionApplication::class)
        ->call('approveApplication', $user);

    expect($user->fresh()->is_auction_allowed)->toBeTrue();
});
