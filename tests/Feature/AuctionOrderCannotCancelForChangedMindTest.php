<?php

use App\Enums\OrderCancellationReason;
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
 * A won auction is a binding sale, so "I no longer want to buy this item"
 * (a plain change of mind) isn't offered as a cancellation reason the way it
 * is for an ordinary direct-sell purchase the buyer can still walk away from.
 */
function changedMindAuctionOrder(): Order
{
    Mail::fake();
    Notification::fake();

    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Auctioned Guitar', 'description' => 'x',
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

    $order = Order::where('auction_id', $auction->id)->firstOrFail();
    // Cancelling an auction order also requires the meetup date to have
    // arrived (see Order::meetupDateHasArrived()) - set here so tests in
    // this file are exercising the cancellation-reason rule specifically,
    // not incidentally blocked by the separate meetup-date gate.
    $order->update(['meetup_time' => now()->subDay()]);

    return $order->fresh();
}

test('the changed-mind reason is not offered for an auction order', function () {
    $order = changedMindAuctionOrder();

    Livewire::actingAs($order->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('toggleCancelForm')
        ->assertDontSee('I no longer want to buy this item')
        ->assertSee('Other reason');
});

test('the changed-mind reason is still offered for a direct-sell order', function () {
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

    Livewire::actingAs($buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('toggleCancelForm')
        ->assertSee('I no longer want to buy this item');
});

test('the default cancellation reason for an auction order is not changed-mind', function () {
    $order = changedMindAuctionOrder();

    Livewire::actingAs($order->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->assertSet('cancelReasonCategory', OrderCancellationReason::OTHER->value);
});

test('submitting changed-mind as the reason is rejected even if forced for an auction order', function () {
    $order = changedMindAuctionOrder();

    Livewire::actingAs($order->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->set('cancelReasonCategory', OrderCancellationReason::CHANGED_MIND->value)
        ->call('confirmCancel');

    expect($order->fresh()->status)->toBe('pending');
});

test('the buyer can still cancel an auction order with a genuine complaint reason', function () {
    $order = changedMindAuctionOrder();

    Livewire::actingAs($order->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->set('cancelReasonCategory', OrderCancellationReason::ITEM_DAMAGED->value)
        ->call('confirmCancel');

    expect($order->fresh()->status)->toBe('cancelled');
});
