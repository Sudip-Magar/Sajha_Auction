<?php

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use App\Services\AuctionEngineService;
use App\Services\AuctionValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createValuationTestSubCategory(): SubCategory
{
    $category = Category::create([
        'name' => 'Electronics',
        'slug' => 'electronics-'.Str::random(5),
        'status' => 'active',
        'sort_order' => 1,
    ]);

    return SubCategory::create([
        'category_id' => $category->id,
        'name' => 'Laptops',
        'slug' => 'laptops-'.Str::random(5),
        'status' => 'active',
        'sort_order' => 1,
    ]);
}

function makeValuationTestProduct(array $overrides = []): Product
{
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $subCategory = createValuationTestSubCategory();

    return Product::create(array_merge([
        'seller_id' => $seller->id,
        'sub_category_id' => $subCategory->id,
        'name' => 'Test Laptop',
        'description' => 'Test',
        'condition' => 'lightly-used',
        'quantity' => 1,
        'listing_type' => 'auction',
        'status' => 'active',
        'is_approved' => true,
    ], $overrides));
}

// 1. Rs. 200,000 item purchased 1 year ago, GOOD condition.
test('estimated value: 1 year old item in good condition depreciates by the 30% bracket with no condition adjustment', function () {
    $estimated = AuctionValuationService::calculateEstimatedValue(200000.0, Carbon::now()->subYear(), 'lightly-used');
    $suggested = AuctionValuationService::calculateSuggestedStartingPrice($estimated);

    expect($estimated)->toBe(140000.0);
    expect($suggested)->toBe(112000.0);
});

// 2. Same item, EXCELLENT condition.
test('estimated value: 1 year old item in excellent condition applies the +5% condition adjustment', function () {
    $estimated = AuctionValuationService::calculateEstimatedValue(200000.0, Carbon::now()->subYear(), 'like-new');
    $suggested = AuctionValuationService::calculateSuggestedStartingPrice($estimated);

    expect($estimated)->toBe(147000.0);
    expect($suggested)->toBe(117600.0);
});

// 3. Rs. 200,000 item purchased 3 years ago.
test('estimated value: 3 year old item depreciates by the 60% bracket', function () {
    // Bracket boundaries are inclusive on the lower end (consistent with the
    // 1-year case above landing in the 1-2yr/30% bracket, not 0-1yr): an item
    // that is exactly 3 years old has entered the 3-4yr/60% bracket.
    $estimated = AuctionValuationService::calculateEstimatedValue(200000.0, Carbon::now()->subYears(3), 'lightly-used');

    expect($estimated)->toBe(80000.0);
});

// 4. New item purchased recently (today).
test('estimated value: item purchased today falls in the 0-6 month / 10% depreciation bracket', function () {
    $estimated = AuctionValuationService::calculateEstimatedValue(200000.0, Carbon::now(), 'new');

    // Base: 200000 * (1 - 0.10) = 180000; Excellent factor 1.05
    expect($estimated)->toBe(189000.0);
});

// 5. Poor-condition item.
test('estimated value: poor condition applies the -20% condition adjustment', function () {
    $estimated = AuctionValuationService::calculateEstimatedValue(200000.0, Carbon::now()->subYear(), 'poor');

    // Base: 200000 * (1 - 0.30) = 140000; Poor factor 0.80
    expect($estimated)->toBe(112000.0);
});

// 6. Missing valuation information.
test('estimated value: returns null instead of inventing a value when required inputs are missing', function () {
    expect(AuctionValuationService::calculateEstimatedValue(null, Carbon::now()->subYear(), 'new'))->toBeNull();
    expect(AuctionValuationService::calculateEstimatedValue(0, Carbon::now()->subYear(), 'new'))->toBeNull();
    expect(AuctionValuationService::calculateEstimatedValue(200000.0, null, 'new'))->toBeNull();
    expect(AuctionValuationService::calculateSuggestedStartingPrice(null))->toBeNull();

    $product = makeValuationTestProduct(['retail_price' => null, 'purchase_date' => null]);
    expect($product->estimated_value)->toBeNull();
    expect($product->suggested_starting_price)->toBeNull();
});

// 7. Future purchase date validation.
test('purchase date validation rejects a future date and accepts today or the past', function () {
    $rules = ['purchase_date' => 'nullable|date|before_or_equal:today'];

    expect(Validator::make(['purchase_date' => now()->addDay()->format('Y-m-d')], $rules)->fails())->toBeTrue();
    expect(Validator::make(['purchase_date' => now()->format('Y-m-d')], $rules)->fails())->toBeFalse();
    expect(Validator::make(['purchase_date' => now()->subYear()->format('Y-m-d')], $rules)->fails())->toBeFalse();

    // Defensively, the service itself never derives a value from a future date either.
    expect(AuctionValuationService::calculateEstimatedValue(200000.0, Carbon::now()->addDay(), 'new'))->toBeNull();
});

// 8 & 9. Bids may exceed the original purchase price and/or the estimated value; the engine never blocks them.
test('auction bids may exceed both the original purchase price and the estimated value', function () {
    $product = makeValuationTestProduct([
        'condition' => 'lightly-used',
        'retail_price' => 200000,
        'purchase_date' => now()->subYear()->format('Y-m-d'),
    ]);

    // Estimated value for this product is 140,000 (30% depreciation, Good factor).
    expect($product->estimated_value)->toBe(140000.0);

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
        'reserve_price' => null,
        'min_bid_increment' => 100,
        'timer_start_seconds' => 60,
        'timer_reset_seconds' => 15,
    ]);

    $bidder = User::factory()->create(['is_auction_allowed' => true]);

    // 210,000 exceeds both the original purchase price (200,000) and the estimated value (140,000).
    $bid = AuctionEngineService::processBid($auction, $bidder, 210000);

    expect($bid->bid_amount)->toBe('210000.00');
    $auction->refresh();
    expect((float) $auction->current_price)->toBe(210000.0);
});

// 10. Original price is never overwritten by the computed estimated value.
test('the original retail price is never overwritten by the computed estimated value', function () {
    $product = makeValuationTestProduct([
        'retail_price' => 200000,
        'purchase_date' => now()->subYear()->format('Y-m-d'),
        'condition' => 'lightly-used',
    ]);

    expect($product->estimated_value)->toBe(140000.0);
    expect((float) $product->fresh()->retail_price)->toBe(200000.0);

    $product->refresh();
    expect((float) $product->retail_price)->toBe(200000.0);
});
