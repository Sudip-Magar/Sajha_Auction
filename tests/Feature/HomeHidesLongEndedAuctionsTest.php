<?php

use App\Livewire\Home;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * The home feed should only ever show an auction while it's upcoming, live,
 * or within an hour of closing (so the result is still visible right after
 * it ends) - not forever, which is what happened before this fix (any
 * product whose auction had ever ended stayed on the feed permanently).
 */
function homeAuctionEndingAt(Carbon $endTime, string $auctionStatus, string $productStatus = 'active'): Product
{
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Feed Window Camera', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'listing_type' => 'auction',
        'status' => $productStatus, 'approval_status' => 'approved',
    ]);
    $auction = Auction::create([
        'product_id' => $product->id, 'auction_type' => 'traditional',
        'start_time' => $endTime->copy()->subDay(), 'end_time' => $endTime,
        'current_price' => 5000, 'status' => $auctionStatus,
    ]);
    TraditionalAuction::create([
        'auction_id' => $auction->id, 'starting_bid' => 5000, 'reserve_price' => 0,
        'min_bid_increment' => 100, 'timer_start_seconds' => 60, 'timer_reset_seconds' => 15,
    ]);

    return $product->fresh();
}

test('an upcoming auction (not started yet) shows on the home feed', function () {
    $product = homeAuctionEndingAt(now()->addDays(2), 'pending');

    Livewire::test(Home::class)->assertSee($product->name);
});

test('a live auction shows on the home feed', function () {
    $product = homeAuctionEndingAt(now()->addHour(), 'active');

    Livewire::test(Home::class)->assertSee($product->name);
});

test('an auction that ended 30 minutes ago still shows on the home feed', function () {
    $product = homeAuctionEndingAt(now()->subMinutes(30), 'completed', 'sold');

    Livewire::test(Home::class)->assertSee($product->name);
});

test('an auction that ended 2 hours ago no longer shows on the home feed', function () {
    $product = homeAuctionEndingAt(now()->subHours(2), 'completed', 'sold');

    Livewire::test(Home::class)->assertDontSee($product->name);
});

test('an auction that ended long ago and was never sold no longer shows on the home feed', function () {
    $product = homeAuctionEndingAt(now()->subDays(3), 'ended_unsold', 'active');

    Livewire::test(Home::class)->assertDontSee($product->name);
});
