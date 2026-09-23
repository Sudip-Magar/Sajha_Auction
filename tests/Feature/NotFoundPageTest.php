<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a nonexistent route renders the custom 404 page', function () {
    $response = $this->get('/this-route-does-not-exist-'.uniqid());

    $response->assertStatus(404)
        ->assertSee('Sajha')
        ->assertSee('Auction')
        ->assertSee('Back to Home')
        ->assertSee('Browse Products')
        ->assertSee('Live Auctions');
});

test('a nonexistent product slug renders the custom 404 page, not a crash', function () {
    $response = $this->get('/products/this-product-does-not-exist');

    $response->assertStatus(404)->assertSee('Back to Home');
});
