<?php

use App\Enums\OrderCancellationReason;
use App\Enums\OrderComplaintStatus;
use App\Enums\OrderDepositStatus;
use App\Enums\PayoutPurpose;
use App\Enums\PayoutStatus;
use App\Livewire\User\OrderDetail;
use App\Mail\OrderCancelledAdminMail;
use App\Mail\SellerPayoutOwedMail;
use App\Models\Admin;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use App\Notifications\OrderCancelledAdminNotification;
use App\Notifications\SellerPayoutOwedNotification;
use App\Services\AuctionEngineService;
use App\Services\OrderCancellationService;
use App\Services\OrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Once an auction order is completed or cancelled, admin's held eSewa
 * deposit needs a destination and both the seller and admin need to know
 * about it - see OrderPaymentService::complete() and
 * OrderCancellationService for the payout/notification wiring this covers.
 */
function payoutTestAuctionOrder(float $bidAmount = 8000): Order
{
    Mail::fake();
    Notification::fake();

    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Payout Test Camera', 'description' => 'x',
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
    Bid::create(['auction_id' => $auction->id, 'bidder_id' => $buyer->id, 'bid_amount' => $bidAmount, 'ip_address' => '127.0.0.1', 'placed_at' => now()]);

    AuctionEngineService::determineWinner($auction->fresh());

    return Order::where('auction_id', $auction->id)->firstOrFail();
}

test('completing an auction order pays the full held deposit out to the seller', function () {
    Admin::create(['name' => 'A', 'email' => 'a'.Str::random(4).'@x.com', 'phone' => '98'.random_int(10000000, 99999999), 'password' => 'password', 'status' => 'active']);

    $order = payoutTestAuctionOrder();
    $order->update(['status' => 'confirmed', 'deposit_status' => 'paid', 'deposit_amount' => 800, 'meetup_time' => now()]);
    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 800, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);

    OrderPaymentService::complete($order->fresh(), $order->seller);

    $payout = PayoutRequest::where('order_id', $order->id)->where('purpose', PayoutPurpose::SELLER_SALE_PROCEEDS)->first();

    expect($payout)->not->toBeNull()
        ->and($payout->recipient_id)->toBe($order->seller_id)
        ->and($payout->amount)->toBe(800.0)
        ->and($payout->payout_status)->toBe(PayoutStatus::AWAITING_DETAILS);

    Notification::assertSentTo($order->seller, SellerPayoutOwedNotification::class, fn ($n) => $n->payout->id === $payout->id);
    Mail::assertQueued(SellerPayoutOwedMail::class, fn ($mail) => $mail->payout->id === $payout->id);
});

test('completing a direct-sell order creates no payout and sends none of the new notifications', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $buyer = User::factory()->create();
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Direct Sell Phone', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 15000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);
    $order = Order::create([
        'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'confirmed', 'total_amount' => 15000,
        'payment_method' => 'cash_on_meetup', 'handover_type' => 'meetup', 'meetup_time' => now(),
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 15000, 'subtotal' => 15000]);

    Mail::fake();
    Notification::fake();

    OrderPaymentService::complete($order->fresh(), $seller);

    expect(PayoutRequest::where('order_id', $order->id)->count())->toBe(0);
    Notification::assertNothingSent();
    Mail::assertNothingQueued();
});

test('an auction cancellation with a forfeit notifies the seller of the payout and admin of the cancellation', function () {
    $admin = Admin::create(['name' => 'A', 'email' => 'a'.Str::random(4).'@x.com', 'phone' => '98'.random_int(10000000, 99999999), 'password' => 'password', 'status' => 'active']);

    $order = payoutTestAuctionOrder();
    $order->update(['deposit_status' => 'paid', 'deposit_amount' => 800]);
    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 800, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);

    OrderCancellationService::cancel($order->fresh(), $order->buyer, OrderCancellationReason::CHANGED_MIND, null);

    $payout = PayoutRequest::where('order_id', $order->id)->where('purpose', PayoutPurpose::SELLER_FORFEIT_SHARE)->first();

    expect($payout)->not->toBeNull();

    Notification::assertSentTo($order->seller, SellerPayoutOwedNotification::class);
    Notification::assertSentTo($admin, OrderCancelledAdminNotification::class);
    Mail::assertQueued(SellerPayoutOwedMail::class);
    Mail::assertQueued(OrderCancelledAdminMail::class);
});

test('a direct-sell cancellation does not send the new admin/seller notifications', function () {
    $admin = Admin::create(['name' => 'A', 'email' => 'a'.Str::random(4).'@x.com', 'phone' => '98'.random_int(10000000, 99999999), 'password' => 'password', 'status' => 'active']);

    $seller = User::factory()->create(['is_seller' => true]);
    $buyer = User::factory()->create();
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Direct Sell Phone', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 15000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);
    $order = Order::create([
        'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'pending', 'total_amount' => 15000,
        'deposit_amount' => 800, 'deposit_status' => 'paid',
        'payment_method' => 'cash_on_meetup', 'handover_type' => 'meetup',
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 15000, 'subtotal' => 15000]);
    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 800, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);

    Mail::fake();
    Notification::fake();

    OrderCancellationService::cancel($order->fresh(), $buyer, OrderCancellationReason::CHANGED_MIND, null);

    // The pre-existing forfeit-share payout still gets created (unrelated,
    // unchanged behaviour) - only the NEW auction-only notifications are
    // withheld.
    expect(PayoutRequest::where('order_id', $order->id)->where('purpose', PayoutPurpose::SELLER_FORFEIT_SHARE)->exists())->toBeTrue();
    Notification::assertNotSentTo($seller, SellerPayoutOwedNotification::class);
    Notification::assertNotSentTo($admin, OrderCancelledAdminNotification::class);
});

test('escalating an "Other reason" cancellation to a complaint opens admin review instead of forfeiting', function () {
    $order = payoutTestAuctionOrder();
    $order->update(['status' => 'confirmed', 'deposit_status' => 'paid', 'deposit_amount' => 800, 'meetup_time' => now()]);
    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 800, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);

    Livewire::actingAs($order->buyer)
        ->test(OrderDetail::class, ['order' => $order])
        ->call('toggleCancelForm')
        ->set('cancelNote', 'It arrived cracked.')
        ->call('runConfirmedEscalateToComplaint');

    $order->refresh();

    expect($order->status)->toBe('cancelled')
        ->and($order->complaint_status)->toBe(OrderComplaintStatus::UNDER_REVIEW)
        ->and($order->cancellation_reason_category)->toBe(OrderCancellationReason::OTHER)
        ->and($order->deposit_status)->toBe(OrderDepositStatus::PAID)
        ->and(PayoutRequest::where('order_id', $order->id)->count())->toBe(0);
});

test('a seller can submit their eSewa details for a payout awaiting them', function () {
    $order = payoutTestAuctionOrder();
    $order->update(['status' => 'confirmed', 'deposit_status' => 'paid', 'deposit_amount' => 800, 'meetup_time' => now()]);
    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 800, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);

    OrderPaymentService::complete($order->fresh(), $order->seller);
    $payout = PayoutRequest::where('order_id', $order->id)->firstOrFail();

    Livewire::actingAs($order->seller)
        ->test(OrderDetail::class, ['order' => $order])
        ->set('payoutEsewaName', 'Test Seller')
        ->set('payoutEsewaPhone', '9800000000')
        ->call('submitPayoutDetails');

    expect($payout->fresh())
        ->esewa_name->toBe('Test Seller')
        ->esewa_phone->toBe('9800000000')
        ->payout_status->toBe(PayoutStatus::PENDING);
});

test('admin can mark a submitted payout as sent', function () {
    $admin = Admin::create(['name' => 'A', 'email' => 'a'.Str::random(4).'@x.com', 'phone' => '98'.random_int(10000000, 99999999), 'password' => 'password', 'status' => 'active']);

    $order = payoutTestAuctionOrder();
    $order->update(['status' => 'confirmed', 'deposit_status' => 'paid', 'deposit_amount' => 800, 'meetup_time' => now()]);
    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 800, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);

    OrderPaymentService::complete($order->fresh(), $order->seller);
    $payout = PayoutRequest::where('order_id', $order->id)->firstOrFail();
    OrderPaymentService::submitPayoutDetails($payout, $order->seller, 'Test Seller', '9800000000', null);

    Livewire::actingAs($admin, 'admin')
        ->test(App\Livewire\Admin\OrderDetail::class, ['order' => $order])
        ->call('markPayoutSent', $payout->id);

    expect($payout->fresh()->payout_status)->toBe(PayoutStatus::SENT);
});
