<?php

use App\Livewire\Home;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function homeSubCategory(): SubCategory
{
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);

    return SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
}

function homeDirectSellProduct(): Product
{
    $seller = User::factory()->create(['is_seller' => true]);

    return Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => homeSubCategory()->id, 'name' => 'Home Second Hand Phone', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 15000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);
}

function homeAuctionProduct(): Product
{
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => homeSubCategory()->id, 'name' => 'Home Auctioned Camera', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'listing_type' => 'auction',
        'status' => 'active', 'approval_status' => 'approved',
    ]);
    $auction = Auction::create([
        'product_id' => $product->id, 'auction_type' => 'traditional',
        'start_time' => now()->subMinute(), 'end_time' => now()->addHour(),
        'current_price' => 5000, 'status' => 'active',
    ]);
    TraditionalAuction::create([
        'auction_id' => $auction->id, 'starting_bid' => 5000, 'reserve_price' => 0,
        'min_bid_increment' => 100, 'timer_start_seconds' => 60, 'timer_reset_seconds' => 15,
    ]);

    return $product->fresh();
}

test('the home page no longer shows the old hardcoded popular-search pills', function () {
    Livewire::test(Home::class)
        ->assertDontSee('Mobile phones')
        ->assertDontSee('Bikes');
});

test('the home page shows both listing types by default', function () {
    $directSell = homeDirectSellProduct();
    $auction = homeAuctionProduct();

    Livewire::test(Home::class)
        ->assertSee($directSell->name)
        ->assertSee($auction->name);
});

test('filtering to Second Hand Product hides auction listings', function () {
    $directSell = homeDirectSellProduct();
    $auction = homeAuctionProduct();

    Livewire::test(Home::class)
        ->call('setListingFilter', 'direct_seller')
        ->assertSet('listingFilter', 'direct_seller')
        ->assertSee($directSell->name)
        ->assertDontSee($auction->name);
});

test('filtering to Auction hides direct-sell listings', function () {
    $directSell = homeDirectSellProduct();
    $auction = homeAuctionProduct();

    Livewire::test(Home::class)
        ->call('setListingFilter', 'auction')
        ->assertSet('listingFilter', 'auction')
        ->assertSee($auction->name)
        ->assertDontSee($directSell->name);
});

test('the view mode toggle switches between list and grid', function () {
    Livewire::test(Home::class)
        ->assertSet('viewMode', 'list')
        ->call('toggleViewMode')
        ->assertSet('viewMode', 'grid')
        ->call('toggleViewMode')
        ->assertSet('viewMode', 'list');
});
