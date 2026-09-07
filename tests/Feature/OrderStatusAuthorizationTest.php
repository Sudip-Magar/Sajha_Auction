<?php

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
        ->test(\App\Livewire\User\OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'completed');

    expect($order->fresh()->status)->toBe('confirmed');
});

test('the seller can mark an order as completed', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller);

    Livewire::actingAs($seller)
        ->test(\App\Livewire\User\OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'completed');

    expect($order->fresh()->status)->toBe('completed');
    expect($order->fresh()->payment_status)->toBe('paid');
});

test('a buyer cannot confirm a pending order', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller, 'pending');

    Livewire::actingAs($buyer)
        ->test(\App\Livewire\User\OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'confirmed');

    expect($order->fresh()->status)->toBe('pending');
});

test('either party can cancel an order', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller, 'pending');

    Livewire::actingAs($buyer)
        ->test(\App\Livewire\User\OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'cancelled');

    expect($order->fresh()->status)->toBe('cancelled');
});

test('an invalid status is rejected', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $order = makeOrderTestOrder($buyer, $seller, 'pending');

    Livewire::actingAs($seller)
        ->test(\App\Livewire\User\OrderDetail::class, ['order' => $order])
        ->call('updateOrderStatus', 'not-a-real-status');

    expect($order->fresh()->status)->toBe('pending');
});
