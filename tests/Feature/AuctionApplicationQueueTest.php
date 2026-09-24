<?php

use App\Enums\DocumentImageType;
use App\Livewire\Admin\AuctionApplication;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function queueTestAdmin(): Admin
{
    return Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin-'.Str::random(5).'@example.com',
        'phone' => '9800000000',
        'password' => 'password',
        'status' => 'active',
    ]);
}

test('the auction application queue only shows applications still awaiting a decision', function () {
    $admin = queueTestAdmin();

    $pendingUser = User::factory()->create(['name' => 'Pending Pema', 'is_seller' => true]);
    $pendingUser->documentImages()->create([
        'type' => DocumentImageType::PASSPORT->value,
        'image' => 'document-images/pending.png',
    ]);

    $approvedUser = User::factory()->create(['name' => 'Approved Anish', 'is_seller' => true, 'is_auction_allowed' => true]);
    $approvedUser->documentImages()->create([
        'type' => DocumentImageType::PASSPORT->value,
        'image' => 'document-images/approved.png',
        'is_approved' => true,
    ]);

    $rejectedUser = User::factory()->create(['name' => 'Rejected Rita', 'is_seller' => true]);
    $rejectedUser->documentImages()->create([
        'type' => DocumentImageType::PASSPORT->value,
        'image' => 'document-images/rejected.png',
        'is_rejected' => true,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(AuctionApplication::class)
        ->assertSee('Pending Pema')
        ->assertDontSee('Approved Anish')
        ->assertDontSee('Rejected Rita');
});

test('a rejected applicant reappears in the queue once they resubmit a document', function () {
    $admin = queueTestAdmin();

    $user = User::factory()->create(['name' => 'Resubmitting Sita', 'is_seller' => true]);
    $document = $user->documentImages()->create([
        'type' => DocumentImageType::PASSPORT->value,
        'image' => 'document-images/rejected.png',
        'is_rejected' => true,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(AuctionApplication::class)
        ->assertDontSee('Resubmitting Sita');

    // Resubmission (JoinAuction) resets a row back to pending directly.
    $document->update(['is_approved' => false, 'is_rejected' => false]);

    Livewire::actingAs($admin, 'admin')
        ->test(AuctionApplication::class)
        ->assertSee('Resubmitting Sita');
});

test('the auction application queue shows whether each applicant already has seller access', function () {
    $admin = queueTestAdmin();

    $seller = User::factory()->create(['name' => 'Seller Sam', 'is_seller' => true]);
    $seller->documentImages()->create(['type' => DocumentImageType::PASSPORT->value, 'image' => 'a.png']);

    $pendingSeller = User::factory()->create(['name' => 'Pending Priya', 'is_seller' => false, 'seller_application_pending' => true]);
    $pendingSeller->documentImages()->create(['type' => DocumentImageType::PASSPORT->value, 'image' => 'b.png']);

    Livewire::actingAs($admin, 'admin')
        ->test(AuctionApplication::class)
        ->assertSeeInOrder(['Seller Sam', 'Has Access'])
        ->assertSeeInOrder(['Pending Priya', 'Request Pending']);
});
