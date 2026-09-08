<?php

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the auction marketplace page is reachable without auth and shows no leaked php text', function () {
    $this->get(route('user.auction'))
        ->assertOk()
        ->assertSee('Live Auctions')
        ->assertDontSee('php <div', false);
});

test('all six info pages render', function () {
    foreach (['safety-tips', 'posting-rules', 'how-it-works', 'help-center', 'buying-guide', 'selling-guide'] as $slug) {
        $this->get(route('info.page', $slug))->assertOk();
    }
});

test('an unknown info page slug 404s', function () {
    $this->get('/info/not-a-real-page')->assertNotFound();
});

test('the newsletter form subscribes an email and rejects duplicates', function () {
    Livewire\Livewire::test(\App\Livewire\Components\User\Footer::class)
        ->set('newsletterEmail', 'reader@example.com')
        ->call('subscribe')
        ->assertHasNoErrors();

    expect(Subscriber::where('email', 'reader@example.com')->exists())->toBeTrue();

    Livewire\Livewire::test(\App\Livewire\Components\User\Footer::class)
        ->set('newsletterEmail', 'reader@example.com')
        ->call('subscribe');

    expect(Subscriber::where('email', 'reader@example.com')->count())->toBe(1);
});

test('the newsletter form rejects an invalid email', function () {
    Livewire\Livewire::test(\App\Livewire\Components\User\Footer::class)
        ->set('newsletterEmail', 'not-an-email')
        ->call('subscribe')
        ->assertHasErrors(['newsletterEmail']);
});
