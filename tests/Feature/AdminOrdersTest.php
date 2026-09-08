<?php

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeAdminOrdersTestOrder(User $buyer, User $seller, array $overrides = []): Order
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

    $order = Order::create(array_merge([
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'status' => 'pending',
        'total_amount' => 50000,
        'payment_method' => 'cash_on_meetup',
    ], $overrides));

    $order->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 50000,
        'subtotal' => 50000,
    ]);

    return $order;
}

it('requires an admin to view orders', function () {
    $this->get(route('admin.orders'))
        ->assertRedirect(route('admin.login'));
});

it('lists both direct-sell and auction orders with their status for admins', function () {
    $admin = Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'phone' => '9800000000',
        'password' => 'password',
        'status' => 'active',
    ]);

    $buyer = User::factory()->create();
    $seller = User::factory()->create();

    $directOrder = makeAdminOrdersTestOrder($buyer, $seller, ['status' => 'completed']);
    $auctionOrder = makeAdminOrdersTestOrder($buyer, $seller, [
        'status' => 'cancelled',
        'deposit_status' => 'refund_owed',
        'deposit_amount' => 5000,
        'cancellation_reason_category' => 'defect_mismatch',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.orders'))
        ->assertOk()
        ->assertSee($directOrder->order_number)
        ->assertSee($auctionOrder->order_number)
        ->assertSee('Refund Owed');
});
