<?php

use App\Livewire\User\OrderDetail;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeOrderTestOrder(User $buyer, User $seller, string $status = 'confirmed'): Order
{
    $category = Category::create([
        'name' => 'Electronics',
        'slug' => 'electronics-'.Str::random(5),
        'status' => 'active',
        'sort_order' => 1,
    ]);
    $subCategory = SubCategory::create([
        'category_id' => $category->id,
        'name' => 'Laptops',
        'slug' => 'laptops-'.Str::random(5),
        'status' => 'active',
        'sort_order' => 1,
    ]);
    $product = Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $subCategory->id,
        'name' => 'Test Laptop',
        'description' => 'Test',
        'condition' => 'lightly-used',
        'quantity' => 1,
        'sale_price' => 50000,
        'listing_type' => 'direct_seller',
        'status' => 'active',
        'is_approved' => true,
    ]);

    $order = Order::create([
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'status' => $status,
        'total_amount' => 50000,
        'payment_method' => 'cash_on_meetup',
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 50000,
        'subtotal' => 50000,
    ]);

    return $order;
}

test('a buyer cannot mark an order as completed', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller);

    Livewire::actingAs($buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'completed');

    expect($order->fresh()->status)->toBe('confirmed');
});

test('the seller can mark an order as completed', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller);

    Livewire::actingAs($seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'completed');

    expect($order->fresh()->status)->toBe('completed');
    expect($order->fresh()->payment_status)->toBe('paid');
});

test('a buyer cannot confirm a pending order', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller, 'pending');

    Livewire::actingAs($buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'confirmed');

    expect($order->fresh()->status)->toBe('pending');
});

test('either party can cancel an order, with a reason', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller, 'pending');

    Livewire::actingAs($buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->set('cancelReasonCategory', 'defect_mismatch')
        ->call('confirmCancel');

    expect($order->fresh())
        ->status->toBe('cancelled')
        ->cancellation_reason_category->toBe('defect_mismatch');
});

test('a cancelled auction-deposit order refunds on defect but forfeits on changed mind', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller, 'pending');
    $order->update(['deposit_status' => 'paid', 'deposit_amount' => 5000]);

    Livewire::actingAs($buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->set('cancelReasonCategory', 'changed_mind')
        ->call('confirmCancel');

    expect($order->fresh()->deposit_status)->toBe('forfeited');

    $order2 = makeOrderTestOrder($buyer, $seller, 'pending');
    $order2->update(['deposit_status' => 'paid', 'deposit_amount' => 5000]);

    Livewire::actingAs($buyer)
        ->test(OrderDetail::class, ['order' => $order2])
        ->set('cancelReasonCategory', 'defect_mismatch')
        ->call('confirmCancel');

    expect($order2->fresh()->deposit_status)->toBe('refund_owed');
});

test('a seller cancelling an auction-deposit order always leaves the deposit refund-owed', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller, 'pending');
    $order->update(['deposit_status' => 'paid', 'deposit_amount' => 5000]);

    Livewire::actingAs($seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('confirmCancel');

    expect($order->fresh())
        ->deposit_status->toBe('refund_owed')
        ->cancellation_reason_category->toBeNull();
});

test('cancelling an auction-win order relists the product instead of leaving it sold forever', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller, 'pending');
    $product = $order->items->first()->product;
    $product->update(['status' => 'sold', 'listing_type' => 'auction']);

    $auction = Auction::create([
        'product_id' => $product->id,
        'auction_type' => 'traditional',
        'start_time' => now()->subDay(),
        'end_time' => now()->subHour(),
        'current_price' => 50000,
        'winning_price' => 50000,
        'winner_id' => $buyer->id,
        'status' => 'completed',
    ]);
    $order->update(['auction_id' => $auction->id]);

    Livewire::actingAs($buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->set('cancelReasonCategory', 'defect_mismatch')
        ->call('confirmCancel');

    expect($product->fresh()->status)->toBe('active')
        ->and($auction->fresh()->status)->toBe('completed');
});

test('cancelling a regular direct-sell order does not touch the product status', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller, 'pending');
    $product = $order->items->first()->product;

    Livewire::actingAs($buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->set('cancelReasonCategory', 'changed_mind')
        ->call('confirmCancel');

    expect($product->fresh()->status)->toBe('active');
});

test('an invalid status is rejected', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller, 'pending');

    Livewire::actingAs($seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'not-a-real-status');

    expect($order->fresh()->status)->toBe('pending');
});
