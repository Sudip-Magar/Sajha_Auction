<?php

use App\Enums\ComplaintMessageSender;
use App\Enums\OrderComplaintStatus;
use App\Livewire\Admin\OrderDetail as AdminOrderDetail;
use App\Livewire\Admin\ProductDetail as AdminProductDetail;
use App\Livewire\Admin\UserDetail as AdminUserDetail;
use App\Livewire\User\OrderDetail as UserOrderDetail;
use App\Models\Admin;
use App\Models\Category;
use App\Models\ComplaintMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerWarning;
use App\Models\SubCategory;
use App\Models\User;
use App\Notifications\ComplaintMessageReceivedNotification;
use App\Notifications\MeetupScheduledNotification;
use App\Notifications\SellerWarningIssuedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function commsOrder(User $buyer, User $seller, array $overrides = []): Order
{
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Thing', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 10000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);

    $order = Order::create(array_merge([
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'status' => 'pending',
        'total_amount' => 10000,
        'payment_method' => 'cash_on_meetup',
        'handover_type' => 'meetup',
    ], $overrides));

    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 10000, 'subtotal' => 10000]);

    return $order;
}

function commsAdmin(): Admin
{
    return Admin::create(['name' => 'A', 'email' => 'a'.Str::random(6).'@x.com', 'phone' => '98'.random_int(10000000, 99999999), 'password' => 'password', 'status' => 'active']);
}

test('the buyer can schedule a meetup on an order that has none yet, and the seller is notified', function () {
    Notification::fake();

    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = commsOrder($buyer, $seller);

    expect($order->meetup_time)->toBeNull();

    Livewire::actingAs($buyer)
        ->test(UserOrderDetail::class, ['order' => $order])
        ->call('toggleMeetupForm')
        ->set('meetup_location', 'Koteshwor Chowk')
        ->set('meetup_date_np', '2082-01-01')
        ->set('meetup_date_en', now()->addDay()->format('Y-m-d'))
        ->set('meetup_time_of_day', '14:30')
        ->call('saveMeetupDetails')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->meetup_location)->toBe('Koteshwor Chowk')
        ->and($order->meetup_time)->not->toBeNull()
        ->and($order->meetup_time->format('H:i'))->toBe('14:30');

    Notification::assertSentTo($seller, MeetupScheduledNotification::class);
});

test('the seller cannot open or submit the buyer-only meetup form', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = commsOrder($buyer, $seller);

    Livewire::actingAs($seller)
        ->test(UserOrderDetail::class, ['order' => $order])
        ->call('toggleMeetupForm')
        ->assertSet('showMeetupForm', false);

    expect($order->fresh()->meetup_time)->toBeNull();
});

test('an admin can send a tracked warning to a seller from the product page', function () {
    Notification::fake();

    $admin = commsAdmin();
    $seller = User::factory()->create(['is_seller' => true]);
    $order = commsOrder(User::factory()->create(), $seller);
    $product = $order->items->first()->product;

    $this->actingAs($admin, 'admin');

    Livewire::test(AdminProductDetail::class, ['product' => $product])
        ->set('warningReason', 'Photos do not match the item condition.')
        ->call('sendWarning')
        ->assertHasNoErrors();

    expect(SellerWarning::count())->toBe(1);
    $warning = SellerWarning::first();
    expect($warning->seller_id)->toBe($seller->id)
        ->and($warning->admin_id)->toBe($admin->id)
        ->and($warning->product_id)->toBe($product->id)
        ->and($warning->reason)->toBe('Photos do not match the item condition.');

    Notification::assertSentTo($seller, SellerWarningIssuedNotification::class);
});

test('an admin can send a general warning from the seller profile page, visible in their warning history', function () {
    Notification::fake();

    $admin = commsAdmin();
    $seller = User::factory()->create(['is_seller' => true]);

    $this->actingAs($admin, 'admin');

    Livewire::test(AdminUserDetail::class, ['user' => $seller])
        ->call('toggleWarningForm')
        ->set('warningReason', 'Repeated late responses to buyers.')
        ->call('sendWarning')
        ->assertHasNoErrors();

    $warning = SellerWarning::first();
    expect($warning->seller_id)->toBe($seller->id)
        ->and($warning->product_id)->toBeNull();

    expect($seller->warnings()->count())->toBe(1);
});

test('a warning shows on the seller\'s own penalties and warnings page', function () {
    $admin = commsAdmin();
    $seller = User::factory()->create(['is_seller' => true]);

    SellerWarning::create([
        'seller_id' => $seller->id,
        'admin_id' => $admin->id,
        'reason' => 'Repeated late responses to buyers.',
    ]);

    $this->actingAs($seller)
        ->get(route('user.penalties'))
        ->assertOk()
        ->assertSee('Repeated late responses to buyers.');
});

test('a warning reason over 500 characters is rejected', function () {
    $admin = commsAdmin();
    $seller = User::factory()->create(['is_seller' => true]);

    $this->actingAs($admin, 'admin');

    Livewire::test(AdminUserDetail::class, ['user' => $seller])
        ->set('warningReason', str_repeat('x', 501))
        ->call('sendWarning')
        ->assertHasErrors(['warningReason' => 'max']);

    expect(SellerWarning::count())->toBe(0);
});

test('the buyer and an admin can exchange messages on an under-review complaint', function () {
    Notification::fake();

    $admin = commsAdmin();
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = commsOrder($buyer, $seller, ['complaint_status' => OrderComplaintStatus::UNDER_REVIEW]);

    Livewire::actingAs($buyer)
        ->test(UserOrderDetail::class, ['order' => $order])
        ->set('complaintMessage', 'When can someone collect the item?')
        ->call('sendComplaintMessage')
        ->assertHasNoErrors();

    expect(ComplaintMessage::count())->toBe(1);
    $buyerMessage = ComplaintMessage::first();
    expect($buyerMessage->sender_role)->toBe(ComplaintMessageSender::BUYER)
        ->and($buyerMessage->sender_id)->toBe($buyer->id);

    Notification::assertSentTo($admin, ComplaintMessageReceivedNotification::class);

    $this->actingAs($admin, 'admin');

    Livewire::test(AdminOrderDetail::class, ['order' => $order->fresh()])
        ->set('complaintMessage', 'We will arrange pickup tomorrow.')
        ->call('sendComplaintMessage')
        ->assertHasNoErrors();

    expect(ComplaintMessage::count())->toBe(2);
    $adminMessage = ComplaintMessage::latest('id')->first();
    expect($adminMessage->sender_role)->toBe(ComplaintMessageSender::ADMIN)
        ->and($adminMessage->sender_id)->toBe($admin->id);

    Notification::assertSentTo($buyer, ComplaintMessageReceivedNotification::class);
});

test('a buyer cannot message on an order that has no complaint', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = commsOrder($buyer, $seller);

    Livewire::actingAs($buyer)
        ->test(UserOrderDetail::class, ['order' => $order])
        ->set('complaintMessage', 'Hello?')
        ->call('sendComplaintMessage');

    expect(ComplaintMessage::count())->toBe(0);
});

test('a stranger cannot message on someone else\'s complaint', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $stranger = User::factory()->create();
    $order = commsOrder($buyer, $seller, ['complaint_status' => OrderComplaintStatus::UNDER_REVIEW]);

    $this->actingAs($stranger)
        ->get(route('user.orders.show', $order))
        ->assertForbidden();

    expect(ComplaintMessage::count())->toBe(0);
});

test('a tampered cancellation reason fails gracefully instead of crashing', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = commsOrder($buyer, $seller);

    Livewire::actingAs($buyer)
        ->test(UserOrderDetail::class, ['order' => $order])
        ->set('cancelReasonCategory', 'not_a_real_reason')
        ->call('confirmCancel');

    expect($order->fresh()->status)->not->toBe('cancelled');
});
