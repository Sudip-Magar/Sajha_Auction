<?php

use App\Enums\OrderComplaintStatus;
use App\Livewire\Admin\ComplaintDetail;
use App\Livewire\Admin\Complaints;
use App\Models\Admin;
use App\Models\Category;
use App\Models\ComplaintMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Notifications\ComplaintMessageReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function complaintTestAdmin(): Admin
{
    return Admin::create([
        'name' => 'Complaint Admin',
        'email' => 'ca-'.Str::random(5).'@example.com',
        'phone' => '98'.random_int(10000000, 99999999),
        'password' => 'password',
        'status' => 'active',
    ]);
}

function complaintTestOrder(User $buyer, User $seller, ?OrderComplaintStatus $complaintStatus = OrderComplaintStatus::UNDER_REVIEW, string $productName = 'Disputed Camera'): Order
{
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => $productName, 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 20000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);

    $order = Order::create([
        'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'cancelled', 'total_amount' => 20000,
        'deposit_amount' => 2000, 'deposit_status' => 'paid', 'payment_method' => 'cash_on_meetup', 'handover_type' => 'meetup',
        'cancellation_reason_category' => 'item_damaged', 'cancellation_note' => 'Lens is cracked.',
        'complaint_status' => $complaintStatus,
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 20000, 'subtotal' => 20000]);
    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 2000, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);

    return $order;
}

test('the complaints queue only lists orders with a complaint, with product/order/buyer/seller/status/action', function () {
    $admin = complaintTestAdmin();
    $buyer = User::factory()->create(['name' => 'Buyer Bina']);
    $seller = User::factory()->create(['name' => 'Seller Sudip']);

    $complaintOrder = complaintTestOrder($buyer, $seller, OrderComplaintStatus::UNDER_REVIEW, 'Disputed Camera');

    $buyer2 = User::factory()->create();
    $seller2 = User::factory()->create();
    $ordinaryOrder = complaintTestOrder($buyer2, $seller2, null, 'Ordinary Product');
    $ordinaryOrder->update(['complaint_status' => null]);

    Livewire::actingAs($admin, 'admin')
        ->test(Complaints::class)
        ->assertSee($complaintOrder->order_number)
        ->assertSee('Disputed Camera')
        ->assertSee('Buyer Bina')
        ->assertSee('Seller Sudip')
        ->assertSee('Awaiting Verdict')
        ->assertDontSee($ordinaryOrder->order_number)
        ->assertDontSee('Ordinary Product');
});

test('the complaints queue can be filtered by status', function () {
    $admin = complaintTestAdmin();

    $underReview = complaintTestOrder(User::factory()->create(), User::factory()->create(), OrderComplaintStatus::UNDER_REVIEW, 'Pending Item');
    $confirmed = complaintTestOrder(User::factory()->create(), User::factory()->create(), OrderComplaintStatus::CONFIRMED_DAMAGED, 'Confirmed Item');

    Livewire::actingAs($admin, 'admin')
        ->test(Complaints::class)
        ->set('statusFilter', OrderComplaintStatus::CONFIRMED_DAMAGED->value)
        ->assertSee('Confirmed Item')
        ->assertDontSee('Pending Item');
});

test('the complaint detail page 404s for an order with no complaint', function () {
    $admin = complaintTestAdmin();
    $order = complaintTestOrder(User::factory()->create(), User::factory()->create(), null);
    $order->update(['complaint_status' => null]);

    Livewire::actingAs($admin, 'admin')
        ->test(ComplaintDetail::class, ['order' => $order])
        ->assertStatus(404);
});

test('an admin can record a damage verdict directly from the complaint detail page', function () {
    $admin = complaintTestAdmin();
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = complaintTestOrder($buyer, $seller, OrderComplaintStatus::UNDER_REVIEW);

    Livewire::actingAs($admin, 'admin')
        ->test(ComplaintDetail::class, ['order' => $order])
        ->set('verdictNote', 'Confirmed via photos.')
        ->call('recordVerdict', true)
        ->assertHasNoErrors();

    expect($order->fresh())
        ->complaint_status->toBe(OrderComplaintStatus::CONFIRMED_DAMAGED)
        ->and($seller->fresh()->is_seller)->toBeFalse();

    expect($order->fresh()->damagePenalty)->not->toBeNull();
});

test('an admin can send a complaint chat message directly from the complaint detail page', function () {
    Notification::fake();
    $admin = complaintTestAdmin();
    $buyer = User::factory()->create();
    $order = complaintTestOrder($buyer, User::factory()->create(), OrderComplaintStatus::UNDER_REVIEW);

    Livewire::actingAs($admin, 'admin')
        ->test(ComplaintDetail::class, ['order' => $order])
        ->set('complaintMessage', 'We are reviewing your complaint.')
        ->call('sendComplaintMessage')
        ->assertHasNoErrors();

    expect(ComplaintMessage::where('order_id', $order->id)->count())->toBe(1);
    Notification::assertSentTo($buyer, ComplaintMessageReceivedNotification::class);
});
