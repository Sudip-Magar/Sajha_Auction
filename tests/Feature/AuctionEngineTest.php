<?php

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use App\Services\AuctionEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createTestSubCategory(): SubCategory
{
    $category = Category::create([
        'name' => 'Electronics',
        'slug' => 'electronics-'.Str::random(5),
        'status' => 'active',
        'sort_order' => 1,
    ]);

    return SubCategory::create([
        'category_id' => $category->id,
        'name' => 'Cameras',
        'slug' => 'cameras-'.Str::random(5),
        'status' => 'active',
        'sort_order' => 1,
    ]);
}

test('algorithm 1: determines winner correctly with reserve price and earliest timestamp tie breaking', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $subCategory = createTestSubCategory();

    $product = Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $subCategory->id,
        'name' => 'Test Camera',
        'description' => 'Test',
        'condition' => 'like-new',
        'quantity' => 1,
        'listing_type' => 'auction',
        'status' => 'active',
        'is_approved' => true,
    ]);

    $auction = Auction::create([
        'product_id' => $product->id,
        'auction_type' => 'traditional',
        'start_time' => now()->subHour(),
        'end_time' => now()->addHour(),
        'current_price' => 5000,
        'status' => 'active',
    ]);

    TraditionalAuction::create([
        'auction_id' => $auction->id,
        'starting_bid' => 5000,
        'reserve_price' => 10000,
        'min_bid_increment' => 100,
        'timer_start_seconds' => 60,
        'timer_reset_seconds' => 15,
    ]);

    $bidder1 = User::factory()->create(['is_auction_allowed' => true]);
    $bidder2 = User::factory()->create(['is_auction_allowed' => true]);

    // Bidder 1 bids 12000 at timestamp t1
    $bid1 = Bid::create([
        'auction_id' => $auction->id,
        'bidder_id' => $bidder1->id,
        'bid_amount' => 12000,
        'ip_address' => '127.0.0.1',
        'placed_at' => '10:00:00',
        'created_at' => now()->subMinutes(10),
    ]);

    // Bidder 2 also bids 12000 at timestamp t2 (later than t1)
    $bid2 = Bid::create([
        'auction_id' => $auction->id,
        'bidder_id' => $bidder2->id,
        'bid_amount' => 12000,
        'ip_address' => '127.0.0.1',
        'placed_at' => '10:05:00',
        'created_at' => now()->subMinutes(5),
    ]);

    $result = AuctionEngineService::determineWinner($auction);

    expect($result['has_winner'])->toBeTrue();
    expect($result['winner_id'])->toBe($bidder1->id);
    expect($result['winning_price'])->toBe(12000.0);
    expect($result['reason'])->toContain('earliest-timestamp tie-breaking rule');
});

test('algorithm 2: calculates dynamic step increment and processes proxy bidding correctly', function () {
    expect(AuctionEngineService::getStepIncrement(500))->toBe(50.0);
    expect(AuctionEngineService::getStepIncrement(2500))->toBe(100.0);
    expect(AuctionEngineService::getStepIncrement(10000))->toBe(250.0);
    expect(AuctionEngineService::getStepIncrement(30000))->toBe(500.0);
    expect(AuctionEngineService::getStepIncrement(60000))->toBe(1000.0);

    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $subCategory = createTestSubCategory();

    $product = Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $subCategory->id,
        'name' => 'Proxy Test Watch',
        'description' => 'Test',
        'condition' => 'new',
        'quantity' => 1,
        'listing_type' => 'auction',
        'status' => 'active',
        'is_approved' => true,
    ]);

    $auction = Auction::create([
        'product_id' => $product->id,
        'auction_type' => 'traditional',
        'start_time' => now()->subHour(),
        'end_time' => now()->addHour(),
        'current_price' => 1000,
        'status' => 'active',
    ]);

    TraditionalAuction::create([
        'auction_id' => $auction->id,
        'starting_bid' => 1000,
        'reserve_price' => 1000,
        'min_bid_increment' => 100,
        'timer_start_seconds' => 60,
        'timer_reset_seconds' => 15,
    ]);

    $bidder1 = User::factory()->create(['is_auction_allowed' => true]);
    $bidder2 = User::factory()->create(['is_auction_allowed' => true]);

    // Bidder 1 registers secret proxy limit of 5000 with initial bid 1100
    AuctionEngineService::processBid($auction, $bidder1, 1100, 5000);
    $auction->refresh();

    // Bidder 2 bids 2000
    AuctionEngineService::processBid($auction, $bidder2, 2000, 2000);
    $auction->refresh();

    // Winner/Leader proxy engine should push standing price above 2000 by step increment (2000 + 100 = 2100)
    expect((float) $auction->current_price)->toBeGreaterThanOrEqual(2100.0);
    expect($auction->bids()->count())->toBe(3);

    $automaticBid = $auction->bids()->orderByDesc('id')->first();

    expect($automaticBid->bidder_id)->toBe($bidder1->id);
    expect($automaticBid->is_proxy)->toBeTrue();
    expect((float) $automaticBid->bid_amount)->toBe(2100.0);

    // The proxy maximum is the bidder's submitted sealed bid at settlement.
    // It must beat the visible manual bid and determine the winner.
    $result = AuctionEngineService::determineWinner($auction);

    expect($result['winner_id'])->toBe($bidder1->id);
    expect($result['winning_price'])->toBe(5000.0);
});

test('algorithm 3: calculates optimal reserve and equilibrium bidding strategy', function () {
    // Myerson Optimal Reserve r* = (v0 + starting_bid) / 2
    $optimalReserve = AuctionEngineService::calculateOptimalReserve(10000, 2000);
    expect($optimalReserve)->toBe(6000.0);

    // Symmetric Bayes-Nash Equilibrium strategy \beta(v) = ((n - 1) / n) * v
    $eqBidFourBidders = AuctionEngineService::calculateEquilibriumBid(10000, 4);
    expect($eqBidFourBidders)->toBe(7500.0); // (3/4) * 10000 = 7500

    $eqBidTwoBidders = AuctionEngineService::calculateEquilibriumBid(10000, 2);
    expect($eqBidTwoBidders)->toBe(5000.0); // (1/2) * 10000 = 5000
});
