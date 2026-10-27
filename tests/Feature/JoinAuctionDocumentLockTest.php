<?php

use App\Enums\DocumentImageType;
use App\Livewire\User\JoinAuction;
use App\Livewire\User\Settings;
use App\Models\DocumentImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('an approved document can no longer be resubmitted even if the component still thinks it can', function () {
    $user = User::factory()->create(['is_auction_allowed' => false]);
    $document = DocumentImage::create([
        'user_id' => $user->id,
        'type' => DocumentImageType::CITIZENSHIPFRONT->value,
        'image' => 'document-images/original.jpg',
        'is_approved' => false,
        'is_rejected' => false,
    ]);

    $component = Livewire::actingAs($user)->test(JoinAuction::class);

    // Approval happens elsewhere (admin action) after the component's state was
    // last loaded, simulating a stale background tab.
    $document->update(['is_approved' => true]);
    $user->update(['is_auction_allowed' => true]);

    $component->set('documentRows.0.type', DocumentImageType::PASSPORT->value)
        ->call('submitApplication');

    expect($document->fresh())
        ->type->toBe(DocumentImageType::CITIZENSHIPFRONT)
        ->is_approved->toBeTrue();
});

test('an approved document cannot be removed through a stale component either', function () {
    $user = User::factory()->create(['is_auction_allowed' => false]);
    $document = DocumentImage::create([
        'user_id' => $user->id,
        'type' => DocumentImageType::CITIZENSHIPFRONT->value,
        'image' => 'document-images/original.jpg',
        'is_approved' => false,
    ]);

    $component = Livewire::actingAs($user)->test(JoinAuction::class);

    // Approval happens elsewhere after the component's state was last loaded.
    $document->update(['is_approved' => true]);
    $user->update(['is_auction_allowed' => true]);

    $component->call('removeDocumentRow', 0);

    expect(DocumentImage::find($document->id))->not->toBeNull();
});

test('settings shows the user\'s documents read-only', function () {
    $user = User::factory()->create();
    DocumentImage::create([
        'user_id' => $user->id,
        'type' => DocumentImageType::PASSPORT->value,
        'image' => 'document-images/passport.jpg',
        'is_approved' => true,
    ]);

    Livewire::actingAs($user)->test(Settings::class)
        ->assertSee('Auction Verification Documents')
        ->assertSee('Approved')
        ->assertDontSee('wire:click="removeDocumentRow');
});
