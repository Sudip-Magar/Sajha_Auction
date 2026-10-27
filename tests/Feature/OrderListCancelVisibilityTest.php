<?php

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
 * An auction win almost always reaches 'confirmed' / 'meetup_scheduled'
 * quickly (that's the whole handover flow), so this bug - the "Cancel
 * Order" link on the orders LIST page vanishing for both parties the
 * moment status left 'pending' - hit auction orders far more than
 * direct-sell ones, even though the restriction itself wasn't auction-
 * specific. OrderDetail's own cancel guard never had this restriction.
 */
function listCancelAuctionOrder(string $status = 'confirmed'): Order
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

    $order = Order::where('auction_id', $auction->id)->firstOrFail();
    $order->update(['status' => $status]);

    return $order;
}

test('the seller does not see a cancel option for a confirmed auction order on the orders list page', function () {
    $order = listCancelAuctionOrder('confirmed');
    $seller = $order->seller;

    // A won auction is a binding sale - the seller can't back out of it here,
    // but their other action (marking it handed over) is still available.
    Livewire::actingAs($seller)
        ->test(Orders::class)
        ->set('tab', 'sales')
        ->assertDontSee('Cancel Order')
        ->assertSee('Mark Completed & Handed Over', false);
});

test('the seller does not see a cancel option for a meetup-scheduled auction order on the orders list page', function () {
    $order = listCancelAuctionOrder('meetup_scheduled');
    $seller = $order->seller;

    Livewire::actingAs($seller)
        ->test(Orders::class)
        ->set('tab', 'sales')
        ->assertDontSee('Cancel Order');
});

test('the seller can still cancel a confirmed direct-sell (non-auction) order from the orders list page', function () {
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
        'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'confirmed', 'total_amount' => 15000,
        'payment_method' => 'cash_on_meetup', 'handover_type' => 'meetup',
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 15000, 'subtotal' => 15000]);

    Livewire::actingAs($seller)
        ->test(Orders::class)
        ->set('tab', 'sales')
        ->assertSee('Cancel Order');
});

test('the buyer can still cancel a confirmed auction order from the orders list page', function () {
    $order = listCancelAuctionOrder('confirmed');
    $buyer = $order->buyer;

    Livewire::actingAs($buyer)
        ->test(Orders::class)
        ->set('tab', 'purchases')
        ->assertSee('Cancel Order');
});

test('cancelling is not offered on the list page once an order is completed or cancelled', function () {
    $order = listCancelAuctionOrder('completed');
    $seller = $order->seller;

    Livewire::actingAs($seller)
        ->test(Orders::class)
        ->set('tab', 'sales')
        ->assertDontSee('Cancel Order');
});
