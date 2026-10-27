<?php

use App\Livewire\User\Products;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Once an auction has settled (sold or ended unsold), there is no longer a
 * "live" room to enter - the bidding is over. Showing the primary green
 * "Live Auction Room" call-to-action on a finished auction misleads the
 * seller into thinking bidding is still open.
 */
function auctionProductWithStatus(string $productStatus, string $auctionStatus): Product
{
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Auctioned Lens', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'listing_type' => 'auction',
        'status' => $productStatus, 'approval_status' => 'approved',
    ]);
    Auction::create([
        'product_id' => $product->id, 'auction_type' => 'traditional',
        'start_time' => now()->subHour(), 'end_time' => now()->subMinute(),
        'current_price' => 5000, 'status' => $auctionStatus,
    ]);

    return $product->fresh();
}

test('the seller sees the Live Auction Room button for an active auction on My Products', function () {
    $product = auctionProductWithStatus('active', 'active');
    $seller = User::find($product->seller_id);

    Livewire::actingAs($seller)
        ->test(Products::class)
        ->assertSee('Live Auction Room')
        ->assertDontSee('View Auction Result');
});

test('the seller sees View Auction Result instead of Live Auction Room for a sold auction on My Products', function () {
    $product = auctionProductWithStatus('sold', 'completed');
    $seller = User::find($product->seller_id);

    Livewire::actingAs($seller)
        ->test(Products::class)
        ->assertDontSee('Live Auction Room')
        ->assertSee('View Auction Result');
});

test('the seller sees View Auction Result for an ended-unsold auction on My Products', function () {
    $product = auctionProductWithStatus('active', 'ended_unsold');
    $seller = User::find($product->seller_id);

    Livewire::actingAs($seller)
        ->test(Products::class)
        ->assertDontSee('Live Auction Room')
        ->assertSee('View Auction Result');
});

test('the sold-auction banner on the product detail page reflects the auction has ended', function () {
    $product = auctionProductWithStatus('sold', 'completed');
    $seller = User::find($product->seller_id);

    $this->actingAs($seller)
        ->get(route('user.products.show', $product->slug))
        ->assertOk()
        ->assertSee('This item was sold through auction.')
        ->assertSee('View Auction Result')
        ->assertDontSee('Enter Live Auction Room');
});
