<?php

use App\Livewire\User\OrderDetail;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use App\Services\AuctionEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * A won auction is a binding sale - the seller who listed it agreed to sell
 * to whoever the bidding process determined as winner, so they should not be
 * able to unilaterally back out the way they can for an ordinary direct-sell
 * listing. Only the buyer may cancel a won-auction order (e.g. a genuine
 * damage/documents complaint).
 */
function noCancelAuctionOrder(): Order
{
    Mail::fake();
    Notification::fake();

    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Auctioned Camera', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'listing_type' => 'auction',
        'status' => 'active', 'approval_status' => 'approved',
    ]);
    $auction = Auction::create([
        'product_id' => $product->id, 'auction_type' => 'traditional',
        'start_time' => now()->subHour(), 'end_time' => now()->subMinute(),
        'current_price' => 5000, 'status' => 'active',
    ]);
    TraditionalAuction::create([
        'auction_id' => $auction->id, 'starting_bid' => 5000, 'reserve_price' => 0,
        'min_bid_increment' => 100, 'timer_start_seconds' => 60, 'timer_reset_seconds' => 15,
    ]);
    $buyer = User::factory()->create(['is_auction_allowed' => true]);
    Bid::create(['auction_id' => $auction->id, 'bidder_id' => $buyer->id, 'bid_amount' => 8000, 'ip_address' => '127.0.0.1', 'placed_at' => now()]);

    AuctionEngineService::determineWinner($auction->fresh());

    return Order::where('auction_id', $auction->id)->firstOrFail();
}

test('the seller does not see the cancel button on the order detail page for an auction order', function () {
    $order = noCancelAuctionOrder();

    Livewire::actingAs($order->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->assertDontSee('Cancel Order');
});

test('the seller cannot open the cancel form for an auction order even if the request is forced', function () {
    $order = noCancelAuctionOrder();

    Livewire::actingAs($order->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('toggleCancelForm')
        ->assertSet('showCancelForm', false);
});

test('the seller cannot cancel an auction order even by calling confirmCancel directly', function () {
    $order = noCancelAuctionOrder();

    Livewire::actingAs($order->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('confirmCancel');

    expect($order->fresh()->status)->toBe('pending');
});

test('the buyer can still cancel an auction order they won', function () {
    $order = noCancelAuctionOrder();
    $order->update(['meetup_time' => now()->subDay()]);

    Livewire::actingAs($order->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->assertSee('Cancel Order')
        ->set('cancelReasonCategory', 'item_damaged')
        ->call('toggleCancelForm')
        ->call('confirmCancel');

    expect($order->fresh()->status)->toBe('cancelled');
});

test('the seller can still cancel an ordinary direct-sell order (no auction involved)', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $buyer = User::factory()->create();
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Second Hand Phone', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 15000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);
    $order = Order::create([
        'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'pending', 'total_amount' => 15000,
        'payment_method' => 'cash_on_meetup', 'handover_type' => 'meetup',
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 15000, 'subtotal' => 15000]);

    Livewire::actingAs($seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->assertSee('Cancel Order')
        ->call('toggleCancelForm')
        ->call('confirmCancel');

    expect($order->fresh()->status)->toBe('cancelled');
});
