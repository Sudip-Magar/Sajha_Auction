<?php

use App\Livewire\User\Cart;
use App\Livewire\User\Orders;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function paginationTestProduct(User $seller, float $price = 1000): Product
{
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);

    return Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $sub->id,
        'name' => 'Item '.Str::random(4),
        'description' => 'x',
        'condition' => 'like-new',
        'quantity' => 5,
        'sale_price' => $price,
        'listing_type' => 'direct_seller',
        'status' => 'active',
        'approval_status' => 'approved',
    ]);
}

test('the cart subtotal and item count cover every item, not just the current page', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();

    foreach (range(1, 12) as $i) {
        $product = paginationTestProduct($seller, 1000);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);
    }

    $component = Livewire::actingAs($buyer)->test(Cart::class);

    expect($component->viewData('subtotal'))->toBe(24000.0)
        ->and($component->viewData('totalItemCount'))->toBe(24)
        ->and($component->viewData('cartItems')->count())->toBe(10);
});

test('my orders paginates purchases and sales independently without resetting each other', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();

    foreach (range(1, 11) as $i) {
        $product = paginationTestProduct($seller, 500);
        $order = Order::create([
            'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'pending',
            'total_amount' => 500, 'payment_method' => 'cash_on_meetup',
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 500, 'subtotal' => 500]);
    }

    $component = Livewire::actingAs($buyer)->test(Orders::class);

    expect($component->viewData('purchases')->total())->toBe(11)
        ->and($component->viewData('purchases')->count())->toBe(10)
        ->and($component->viewData('sales')->total())->toBe(0);

    $component->call('gotoPage', 2, 'purchases-page');
    expect($component->viewData('purchases')->count())->toBe(1);
});
