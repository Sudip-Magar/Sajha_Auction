<?php

use App\Enums\DamagePenaltyStatus;
use App\Enums\OrderCancellationReason;
use App\Enums\OrderComplaintStatus;
use App\Enums\PaymentTransactionType;
use App\Livewire\Admin\OrderDetail as AdminOrderDetail;
use App\Livewire\Components\User\Navbar;
use App\Mail\DamagePenaltyIssuedMail;
use App\Models\Admin;
use App\Models\Category;
use App\Models\DamagePenalty;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerDamageStrike;
use App\Models\SubCategory;
use App\Models\User;
use App\Notifications\SellerDamagePenaltyIssuedNotification;
use App\Services\DamagePenaltyService;
use App\Services\OrderCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function penaltyOrder(User $buyer, User $seller, float $total = 50000, float $paidOnline = 5000): Order
{
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Thing', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => $total, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);

    $order = Order::create([
        'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'confirmed', 'total_amount' => $total,
        'deposit_amount' => round($total * 0.10, 2), 'deposit_status' => $paidOnline > 0 ? 'paid' : 'pending',
        'payment_method' => 'cash_on_meetup', 'handover_type' => 'meetup',
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => $total, 'subtotal' => $total]);

    if ($paidOnline > 0) {
        $order->transactions()->create(['type' => 'deposit_paid', 'amount' => $paidOnline, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);
    }

    return $order;
}

function makePenaltyAdmin(): Admin
{
    return Admin::create(['name' => 'A', 'email' => 'a'.Str::random(4).'@x.com', 'phone' => '98'.random_int(10000000, 99999999), 'password' => 'password', 'status' => 'active']);
}

test('a confirmed-damaged verdict issues a 30% penalty due in 7 days and snapshots prior access', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => false]);
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);

    OrderCancellationService::cancel($order->load('items.product'), $order->buyer, OrderCancellationReason::ITEM_DAMAGED, null);
    $penalty = OrderCancellationService::recordDamageVerdict($order->fresh(), makePenaltyAdmin(), true, null);

    expect($penalty->amount)->toBe(12000.0)
        ->and($penalty->status)->toBe(DamagePenaltyStatus::PENDING)
        ->and($penalty->prior_is_seller)->toBeTrue()
        ->and($penalty->prior_is_auction_allowed)->toBeFalse()
        ->and($penalty->verdict_recorded_at->diffInDays($penalty->due_at))->toBe(7.0)
        ->and($seller->fresh()->is_seller)->toBeFalse();
});

test('a confirmed-damaged verdict clears a stale seller-application-pending flag and emails the seller', function () {
    Mail::fake();

    // Simulates the real stale state this test guards against: access
    // already revoked once before, but the pending flag was never reset.
    $seller = User::factory()->create(['is_seller' => false, 'is_auction_allowed' => false, 'seller_application_pending' => true]);
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);

    OrderCancellationService::cancel($order->load('items.product'), $order->buyer, OrderCancellationReason::ITEM_DAMAGED, null);
    $penalty = OrderCancellationService::recordDamageVerdict($order->fresh(), makePenaltyAdmin(), true, null);

    expect($seller->fresh()->seller_application_pending)->toBeFalse();

    Mail::assertQueued(DamagePenaltyIssuedMail::class, fn ($mail) => $mail->penalty->id === $penalty->id);
});

test('the navbar shows a pay-penalty link instead of stale seller/auction menu items while a penalty is unpaid', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => false]);
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);

    OrderCancellationService::cancel($order->load('items.product'), $order->buyer, OrderCancellationReason::ITEM_DAMAGED, null);
    OrderCancellationService::recordDamageVerdict($order->fresh(), makePenaltyAdmin(), true, null);

    Livewire::actingAs($seller->fresh())
        ->test(Navbar::class)
        ->assertSee('Pay Damage Penalty')
        ->assertDontSee('Seller Request Pending')
        ->assertDontSee('Join Auction');
});

test('clicking the penalty or warning notification takes the seller to the penalties page', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => false]);
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);

    OrderCancellationService::cancel($order->load('items.product'), $order->buyer, OrderCancellationReason::ITEM_DAMAGED, null);
    $penalty = OrderCancellationService::recordDamageVerdict($order->fresh(), makePenaltyAdmin(), true, null);

    $notification = $seller->fresh()->notifications()->where('type', SellerDamagePenaltyIssuedNotification::class)->firstOrFail();

    Livewire::actingAs($seller->fresh())
        ->test(Navbar::class)
        ->call('handleNotificationClick', $notification->id)
        ->assertRedirect(route('user.penalties'));
});

test('the penalties and warnings page lists the pending penalty with a pay link, and is reachable with seller access revoked', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => false]);
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);

    OrderCancellationService::cancel($order->load('items.product'), $order->buyer, OrderCancellationReason::ITEM_DAMAGED, null);
    $penalty = OrderCancellationService::recordDamageVerdict($order->fresh(), makePenaltyAdmin(), true, null);

    expect($seller->fresh()->is_seller)->toBeFalse();

    $this->actingAs($seller->fresh())
        ->get(route('user.penalties'))
        ->assertOk()
        ->assertSee('Rs. 12,000.00')
        ->assertSee(route('payment.esewa.penalty-initiate', $penalty), false);
});

test('the seller can pay the penalty via a fresh eSewa link and the transaction is logged', function () {
    config(['services.esewa.secret_key' => '8gBm/:&EnhH.1/q', 'services.esewa.status_url' => 'https://rc.esewa.com.np/api/epay/transaction/status/']);

    $seller = User::factory()->create(['is_seller' => true]);
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);
    OrderCancellationService::cancel($order->load('items.product'), $order->buyer, OrderCancellationReason::ITEM_DAMAGED, null);
    $penalty = OrderCancellationService::recordDamageVerdict($order->fresh(), makePenaltyAdmin(), true, null);

    $this->actingAs($seller)
        ->get(route('payment.esewa.penalty-initiate', $penalty))
        ->assertOk()
        ->assertSee('transaction_uuid', false);

    $penalty->refresh();
    expect($penalty->transaction_uuid)->not->toBeNull();

    $payload = [
        'transaction_code' => 'PEN123', 'status' => 'COMPLETE', 'total_amount' => number_format($penalty->amount, 2, '.', ''),
        'transaction_uuid' => $penalty->transaction_uuid, 'product_code' => 'EPAYTEST',
        'signed_field_names' => 'transaction_code,status,total_amount,transaction_uuid,product_code',
    ];
    $message = collect(explode(',', $payload['signed_field_names']))->map(fn ($f) => "{$f}={$payload[$f]}")->implode(',');
    $payload['signature'] = base64_encode(hash_hmac('sha256', $message, '8gBm/:&EnhH.1/q', true));
    Http::fake(['rc.esewa.com.np/*' => Http::response(['status' => 'COMPLETE'])]);

    $this->actingAs($seller)
        ->get(route('payment.esewa.penalty-success', ['data' => base64_encode(json_encode($payload))]))
        ->assertRedirect(route('dashboard'));

    expect($penalty->fresh()->status)->toBe(DamagePenaltyStatus::PAID)
        ->and((float) $order->transactions()->where('type', PaymentTransactionType::DAMAGE_PENALTY_PAID)->sum('amount'))->toBe($penalty->amount)
        ->and(SellerDamageStrike::where('seller_id', $seller->id)->count())->toBe(1);
});

test('a stranger cannot pay someone else\'s penalty', function () {
    $seller = User::factory()->create();
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);
    OrderCancellationService::cancel($order->load('items.product'), $order->buyer, OrderCancellationReason::ITEM_DAMAGED, null);
    $penalty = OrderCancellationService::recordDamageVerdict($order->fresh(), makePenaltyAdmin(), true, null);

    $this->actingAs(User::factory()->create())
        ->get(route('payment.esewa.penalty-initiate', $penalty))
        ->assertForbidden();
});

test('admin restores exactly the access the seller had before revocation', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => false]);
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);
    $penalty = DamagePenaltyService::issue($order);
    DamagePenaltyService::recordPayment($penalty);

    $admin = makePenaltyAdmin();
    expect(DamagePenaltyService::restoreAccess($penalty->fresh(), $admin))->toBeTrue();

    expect($seller->fresh())
        ->is_seller->toBeTrue()
        ->is_auction_allowed->toBeFalse();
});

test('recording the same payment twice does not duplicate the ledger entry or double-count a strike', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);
    $penalty = DamagePenaltyService::issue($order);

    // Simulates a duplicate/replayed callback: recordPayment() called twice
    // for the same already-fetched penalty instance.
    DamagePenaltyService::recordPayment($penalty);
    DamagePenaltyService::recordPayment($penalty);

    expect($order->transactions()->where('type', PaymentTransactionType::DAMAGE_PENALTY_PAID)->count())->toBe(1)
        ->and(SellerDamageStrike::where('seller_id', $seller->id)->count())->toBe(1);
});

test('a second unresolved penalty keeps the true prior access state, not the already-revoked one', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $orderA = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);
    $orderB = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);

    $penaltyA = DamagePenaltyService::issue($orderA);
    // At this point access is already revoked; issuing a second penalty
    // must not snapshot that revoked state as penalty B's "prior" state.
    $penaltyB = DamagePenaltyService::issue($orderB);

    expect($penaltyB->prior_is_seller)->toBeTrue()
        ->and($penaltyB->prior_is_auction_allowed)->toBeTrue();

    DamagePenaltyService::recordPayment($penaltyA->fresh());

    // Restoring via A must be refused while B is still unresolved - access
    // would otherwise come back while a penalty remains unpaid.
    expect(DamagePenaltyService::restoreAccess($penaltyA->fresh(), makePenaltyAdmin()))->toBeFalse()
        ->and($seller->fresh()->is_seller)->toBeFalse();

    DamagePenaltyService::recordPayment($penaltyB->fresh());

    expect(DamagePenaltyService::restoreAccess($penaltyA->fresh(), makePenaltyAdmin()))->toBeTrue()
        ->and($seller->fresh())->is_seller->toBeTrue()->is_auction_allowed->toBeTrue();
});

test('a third strike permanently bans the seller and blocks the normal restore action', function () {
    $seller = User::factory()->create(['is_seller' => true]);

    foreach (range(1, 3) as $i) {
        $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);
        $penalty = DamagePenaltyService::issue($order);
        DamagePenaltyService::recordPayment($penalty);
    }

    $seller->refresh();
    expect($seller->is_permanently_banned)->toBeTrue()
        ->and($seller->permanent_ban_reason)->toBe('three_strikes')
        ->and(DamagePenaltyService::restoreAccess($penalty->fresh(), makePenaltyAdmin()))->toBeFalse()
        ->and($seller->fresh()->is_seller)->toBeFalse();
});

test('an unpaid penalty past 7 days deactivates the whole account and flags legal action', function () {
    $seller = User::factory()->create(['status' => 'active']);
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);
    $penalty = DamagePenaltyService::issue($order);
    $penalty->update(['due_at' => now()->subDay()]);

    DamagePenaltyService::expireOverdue();

    expect($penalty->fresh()->status)->toBe(DamagePenaltyStatus::EXPIRED)
        ->and($penalty->fresh()->legal_action_flagged)->toBeTrue()
        ->and($seller->fresh()->status)->toBe('inactive');
});

test('admin records the verdict from the order detail page', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $order = penaltyOrder(User::factory()->create(), $seller, 40000, 4000);
    OrderCancellationService::cancel($order->load('items.product'), $order->buyer, OrderCancellationReason::NOT_AS_DESCRIBED, null);
    $admin = makePenaltyAdmin();

    $this->actingAs($admin, 'admin');

    Livewire::test(AdminOrderDetail::class, ['order' => $order])
        ->call('recordVerdict', true);

    expect($order->fresh()->complaint_status)->toBe(OrderComplaintStatus::CONFIRMED_DAMAGED)
        ->and(DamagePenalty::where('order_id', $order->id)->exists())->toBeTrue();
});
