<?php

use App\Enums\OrderDepositStatus;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Unlike a direct-sell checkout (which collects the meetup upfront and never
 * requires a deposit), an auction win is created automatically with no
 * deposit paid and no meetup arranged. The seller must not be able to
 * confirm the order until the buyer has done both.
 */
function unreadyAuctionOrder(): Order
{
    Mail::fake();
    Notification::fake();

    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Auctioned Watch', 'description' => 'x',
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

test('the seller cannot confirm an auction order while the deposit is unpaid', function () {
    $order = unreadyAuctionOrder();
    $order->update(['deposit_status' => OrderDepositStatus::PENDING, 'meetup_time' => now()->addDay()]);

    Livewire::actingAs($order->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'confirmed');

    expect($order->fresh()->status)->toBe('pending');
});

test('the seller cannot confirm an auction order with no meetup scheduled', function () {
    $order = unreadyAuctionOrder();
    $order->update(['deposit_status' => OrderDepositStatus::PAID, 'meetup_time' => null]);

    Livewire::actingAs($order->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'confirmed');

    expect($order->fresh()->status)->toBe('pending');
});

test('the seller can confirm an auction order once the deposit is paid and a meetup is scheduled', function () {
    $order = unreadyAuctionOrder();
    $order->update(['deposit_status' => OrderDepositStatus::PAID, 'meetup_time' => Carbon::now()->addDay()]);

    Livewire::actingAs($order->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'confirmed');

    expect($order->fresh()->status)->toBe('confirmed');
});

test('the same deposit and meetup precondition applies from the orders list page', function () {
    $order = unreadyAuctionOrder();
    $order->update(['deposit_status' => OrderDepositStatus::PENDING, 'meetup_time' => null]);

    Livewire::actingAs($order->seller)
        ->test(Orders::class)
        ->call('updateOrderStatus', $order->id, 'confirmed');

    expect($order->fresh()->status)->toBe('pending');
});

test('a direct-sell order has no deposit or meetup gate on confirmation', function () {
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
        ->call('updateOrderStatus', 'confirmed');

    expect($order->fresh()->status)->toBe('confirmed');
});
