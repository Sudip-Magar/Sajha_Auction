<?php

use App\Enums\OrderCancellationReason;
use App\Enums\PaymentTransactionType;
use App\Livewire\Admin\OrderDetail as AdminOrderDetail;
use App\Livewire\User\Messages;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\DamagePenalty;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\OrderCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('a user cannot view another user\'s conversation via the paginated-sidebar fallback', function () {
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => User::factory()->create()->id, 'sub_category_id' => $sub->id, 'name' => 'Item', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 1000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);

    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $conversation = Conversation::create(['product_id' => $product->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'last_message_at' => now()]);

    $stranger = User::factory()->create();

    $component = Livewire::actingAs($stranger)->test(Messages::class);
    $component->set('selectedConversationId', $conversation->id);

    expect($component->viewData('selectedConversation'))->toBeNull();
});

test('recording a damage verdict twice cannot double the refund or the penalty', function () {
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $seller = User::factory()->create(['is_seller' => true]);
    $buyer = User::factory()->create();
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Item', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 40000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);

    $order = Order::create([
        'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'confirmed', 'total_amount' => 40000,
        'deposit_amount' => 4000, 'deposit_status' => 'paid', 'payment_method' => 'cash_on_meetup', 'handover_type' => 'meetup',
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 40000, 'subtotal' => 40000]);
    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 4000, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);

    OrderCancellationService::cancel($order->load('items.product'), $buyer, OrderCancellationReason::ITEM_DAMAGED, null);

    $admin = Admin::create(['name' => 'A', 'email' => 'a'.Str::random(4).'@x.com', 'phone' => '98'.random_int(10000000, 99999999), 'password' => 'password', 'status' => 'active']);

    $first = OrderCancellationService::recordDamageVerdict($order->fresh(), $admin, true, null);
    $second = OrderCancellationService::recordDamageVerdict($order->fresh(), $admin, true, null);

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull()
        ->and($order->transactions()->where('type', PaymentTransactionType::REFUND_ISSUED)->count())->toBe(1)
        ->and(DamagePenalty::where('order_id', $order->id)->count())->toBe(1);
});

test('an admin recording a verdict twice from the order-detail page does not duplicate anything', function () {
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $seller = User::factory()->create(['is_seller' => true]);
    $buyer = User::factory()->create();
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Item', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 40000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);

    $order = Order::create([
        'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'confirmed', 'total_amount' => 40000,
        'deposit_amount' => 4000, 'deposit_status' => 'paid', 'payment_method' => 'cash_on_meetup', 'handover_type' => 'meetup',
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 40000, 'subtotal' => 40000]);
    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 4000, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);
    OrderCancellationService::cancel($order->load('items.product'), $buyer, OrderCancellationReason::ITEM_DAMAGED, null);

    $admin = Admin::create(['name' => 'A', 'email' => 'a'.Str::random(4).'@x.com', 'phone' => '98'.random_int(10000000, 99999999), 'password' => 'password', 'status' => 'active']);
    test()->actingAs($admin, 'admin');

    $component = Livewire::test(AdminOrderDetail::class, ['order' => $order]);
    $component->call('recordVerdict', true);
    $component->call('recordVerdict', true);

    expect($order->transactions()->where('type', PaymentTransactionType::REFUND_ISSUED)->count())->toBe(1)
        ->and(DamagePenalty::where('order_id', $order->id)->count())->toBe(1);
});
