<?php

use App\Enums\DocumentImageType;
use App\Livewire\Admin\AuctionApplication;
use App\Livewire\Admin\AuctionApplicationDetail;
use App\Livewire\User\JoinAuction;
use App\Models\Admin;
use App\Models\User;
use App\Notifications\AuctionApplicationApprovedNotification;
use App\Notifications\AuctionApplicationRejectedNotification;
use App\Notifications\AuctionApplicationSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('notifies admins when a user submits an auction application', function () {
    Storage::fake('public');
    Notification::fake();

    $user = User::factory()->create();
    $admin = Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'phone' => '9800000000',
        'password' => 'password',
        'status' => 'active',
    ]);

    $this->actingAs($user);

    Livewire::test(JoinAuction::class)
        ->set('documentRows', [[
            'id' => null,
            'type' => DocumentImageType::PASSPORT->value,
            'image_path' => null,
            'image' => UploadedFile::fake()->image('passport.png'),
        ]])
        ->call('submitApplication')
        ->assertHasNoErrors();

    Notification::assertSentTo($admin, AuctionApplicationSubmittedNotification::class);

    expect($user->fresh()->documentImages)->toHaveCount(1)
        ->and($user->fresh()->documentImages->first()->type)->toBe(DocumentImageType::PASSPORT);
});

it('allows an admin to approve an auction application from the list page', function () {
    Notification::fake();

    $admin = Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'phone' => '9800000000',
        'password' => 'password',
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'is_auction_allowed' => false,
    ]);

    $user->documentImages()->create([
        'type' => DocumentImageType::PASSPORT->value,
        'image' => 'document-images/passport.png',
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(AuctionApplication::class)
        ->call('approveApplication', $user);

    $user->refresh();

    expect($user->is_auction_allowed)->toBeTrue()
        ->and($user->documentImages()->first()->is_approved)->toBeTrue()
        ->and($user->documentImages()->first()->is_rejected)->toBeFalse();

    Notification::assertSentTo($user, AuctionApplicationApprovedNotification::class);
});

it('allows an admin to reject an auction application from the detail page', function () {
    Notification::fake();

    $admin = Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'phone' => '9800000000',
        'password' => 'password',
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'is_auction_allowed' => true,
    ]);

    $user->documentImages()->create([
        'type' => DocumentImageType::DRIVING_LICENSE->value,
        'image' => 'document-images/license.png',
        'is_approved' => true,
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(AuctionApplicationDetail::class, ['user' => $user])
        ->call('rejectApplication');

    $user->refresh();

    expect($user->is_auction_allowed)->toBeFalse()
        ->and($user->documentImages()->first()->is_approved)->toBeFalse()
        ->and($user->documentImages()->first()->is_rejected)->toBeTrue();

    Notification::assertSentTo($user, AuctionApplicationRejectedNotification::class);
});
