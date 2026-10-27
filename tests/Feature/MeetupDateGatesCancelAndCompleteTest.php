<?php

use App\Livewire\User\OrderDetail;
use App\Livewire\User\Orders;
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
 * Neither the buyer's auction cancellation nor "Mark Completed & Handed
 * Over" makes sense before the meetup the order revolves around has
 * actually happened - see Order::meetupDateHasArrived(), which compares
 * calendar dates (today or earlier), not exact times.
 */
function meetupGateAuctionOrder(): Order
{
    Mail::fake();
    Notification::fake();

    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Meetup Gate Camera', 'description' => 'x',
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

function directSellOrderForMeetupGate(): Order
{
    $seller = User::factory()->create(['is_seller' => true]);
    $buyer = User::factory()->create();
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Second Hand Tablet', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 15000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);
    $order = Order::create([
        'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'confirmed', 'total_amount' => 15000,
        'payment_method' => 'cash_on_meetup', 'handover_type' => 'meetup',
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 15000, 'subtotal' => 15000]);

    return $order;
}

// --- Order::meetupDateHasArrived() ---

test('meetupDateHasArrived is false with no meetup scheduled', function () {
    $order = meetupGateAuctionOrder();

    expect($order->meetupDateHasArrived())->toBeFalse();
});

test('meetupDateHasArrived is true for a meetup scheduled today', function () {
    $order = meetupGateAuctionOrder();
    $order->update(['meetup_time' => now()]);

    expect($order->fresh()->meetupDateHasArrived())->toBeTrue();
});

test('meetupDateHasArrived is true for a meetup scheduled yesterday', function () {
    $order = meetupGateAuctionOrder();
    $order->update(['meetup_time' => now()->subDay()]);

    expect($order->fresh()->meetupDateHasArrived())->toBeTrue();
});

test('meetupDateHasArrived is false for a meetup scheduled tomorrow', function () {
    $order = meetupGateAuctionOrder();
    $order->update(['meetup_time' => now()->addDay()]);

    expect($order->fresh()->meetupDateHasArrived())->toBeFalse();
});

// --- Buyer cancelling an auction order ---

test('the buyer cannot cancel an auction order before the meetup date arrives', function () {
    $order = meetupGateAuctionOrder();
    $order->update(['meetup_time' => now()->addDay()]);

    Livewire::actingAs($order->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('toggleCancelForm')
        ->assertSet('showCancelForm', false)
        ->set('cancelReasonCategory', 'item_damaged')
        ->call('confirmCancel');

    expect($order->fresh()->status)->toBe('pending');
});

test('the buyer can cancel an auction order once the meetup date arrives', function () {
    $order = meetupGateAuctionOrder();
    $order->update(['meetup_time' => now()]);

    Livewire::actingAs($order->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('toggleCancelForm')
        ->assertSet('showCancelForm', true)
        ->set('cancelReasonCategory', 'item_damaged')
        ->call('confirmCancel');

    expect($order->fresh()->status)->toBe('cancelled');
});

test('a direct-sell order has no meetup-date gate on cancellation', function () {
    $order = directSellOrderForMeetupGate();

    Livewire::actingAs($order->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('toggleCancelForm')
        ->assertSet('showCancelForm', true)
        ->call('confirmCancel');

    expect($order->fresh()->status)->toBe('cancelled');
});

// --- Mark Completed & Handed Over ---

test('the seller cannot mark an auction order completed before the meetup date arrives', function () {
    $order = meetupGateAuctionOrder();
    $order->update(['status' => 'confirmed', 'meetup_time' => now()->addDay()]);

    Livewire::actingAs($order->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'completed');

    expect($order->fresh()->status)->toBe('confirmed');
});

test('the seller can mark an auction order completed once the meetup date arrives', function () {
    $order = meetupGateAuctionOrder();
    $order->update(['status' => 'confirmed', 'meetup_time' => now()]);

    Livewire::actingAs($order->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'completed');

    expect($order->fresh()->status)->toBe('completed');
});

test('the seller cannot mark a direct-sell order completed before the meetup date arrives', function () {
    $order = directSellOrderForMeetupGate();
    $order->update(['meetup_time' => now()->addDay()]);

    Livewire::actingAs($order->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'completed');

    expect($order->fresh()->status)->toBe('confirmed');
});

test('the same meetup-date gate applies to Mark Completed from the orders list page', function () {
    $order = meetupGateAuctionOrder();
    $order->update(['status' => 'confirmed', 'meetup_time' => now()->addDay()]);

    Livewire::actingAs($order->seller)
        ->test(Orders::class)
        ->call('updateOrderStatus', $order->id, 'completed');

    expect($order->fresh()->status)->toBe('confirmed');
});
