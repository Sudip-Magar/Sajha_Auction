<?php

use App\Enums\OrderCancellationReason;
use App\Enums\OrderComplaintStatus;
use App\Enums\OrderDepositStatus;
use App\Enums\PaymentTransactionParty;
use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use App\Enums\PayoutRecipientRole;
use App\Enums\PayoutStatus;
use App\Enums\SellerDebtStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\SellerDebt;
use App\Models\SubCategory;
use App\Models\User;
use App\Notifications\ComplaintFiledNotification;
use App\Services\EsewaPaymentService;
use App\Services\OrderCancellationService;
use App\Services\OrderPaymentService;
use App\Services\SellerDebtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function ledgerOrder(User $buyer, User $seller, float $total = 50000, float $paidOnline = 5000): Order
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

function makeLedgerAdmin(): Admin
{
    return Admin::create(['name' => 'A', 'email' => 'a'.Str::random(4).'@x.com', 'phone' => '98'.random_int(10000000, 99999999), 'password' => 'password', 'status' => 'active']);
}

test('totals are derived from transactions and completing the order records the cash balance', function () {
    $order = ledgerOrder(User::factory()->create(), $seller = User::factory()->create(), 50000, 20000);

    expect($order->paidOnline())->toBe(20000.0)
        ->and($order->remainingAmount())->toBe(30000.0);

    OrderPaymentService::complete($order->load('items.product'), $seller);

    expect($order->fresh()->remainingAmount())->toBe(0.0)
        ->and($order->paidCash())->toBe(30000.0)
        ->and($order->fresh()->status)->toBe('completed');
});

test('an order paid in full online needs no cash at handover', function () {
    $order = ledgerOrder(User::factory()->create(), $seller = User::factory()->create(), 50000, 50000);

    OrderPaymentService::complete($order->load('items.product'), $seller);

    expect($order->paidCash())->toBe(0.0)
        ->and($order->transactions()->where('type', PaymentTransactionType::BALANCE_PAID_CASH)->count())->toBe(0);
});

test('online payment range starts at the deposit minimum and is capped at the remaining total', function () {
    $order = ledgerOrder(User::factory()->create(), User::factory()->create(), 50000, 0);
    $service = app(EsewaPaymentService::class);

    expect($service->allowedRange($order))->toBe(['min' => 5000.0, 'max' => 50000.0]);

    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 5000, 'payment_method' => 'esewa', 'status' => 'completed']);

    expect($service->allowedRange($order->fresh()))->toBe(['min' => 1.0, 'max' => 45000.0]);
});

test('a payment below the minimum deposit is rejected with the exact minimum', function () {
    $buyer = User::factory()->create();
    $order = ledgerOrder($buyer, User::factory()->create(), 50000, 0);

    $this->actingAs($buyer)
        ->get(route('payment.esewa.initiate', ['order' => $order, 'amount' => 1000]))
        ->assertRedirect(route('user.orders.show', $order))
        ->assertSessionHas('esewa_error', fn (string $message): bool => str_contains($message, '5,000.00'));

    $this->actingAs($buyer)
        ->get(route('payment.esewa.initiate', ['order' => $order, 'amount' => 60000]))
        ->assertSessionHas('esewa_error');

    $this->actingAs($buyer)
        ->get(route('payment.esewa.initiate', ['order' => $order, 'amount' => 20000]))
        ->assertOk();

    expect((float) $order->transactions()->where('status', PaymentTransactionStatus::PENDING)->sum('amount'))->toBe(20000.0);
});

test('changing your mind forfeits only the minimum deposit, split 80/20, and refunds the excess', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = ledgerOrder($buyer, $seller, 50000, 12000);

    OrderCancellationService::cancel($order->load('items.product'), $buyer, OrderCancellationReason::CHANGED_MIND, null);

    $forfeits = $order->transactions()->where('type', PaymentTransactionType::DEPOSIT_FORFEITED)->get();

    expect((float) $forfeits->sum('amount'))->toBe(5000.0)
        ->and((float) $forfeits->firstWhere('party', PaymentTransactionParty::ADMIN)->amount)->toBe(4000.0)
        ->and((float) $forfeits->firstWhere('party', PaymentTransactionParty::SELLER)->amount)->toBe(1000.0)
        ->and((float) $order->transactions()->where('type', PaymentTransactionType::REFUND_ISSUED)->sum('amount'))->toBe(7000.0)
        ->and($order->fresh()->deposit_status)->toBe(OrderDepositStatus::FORFEITED)
        ->and($order->payoutRequests()->where('recipient_role', PayoutRecipientRole::SELLER)->first()->amount)->toBe(1000.0)
        ->and($order->payoutRequests()->where('recipient_role', PayoutRecipientRole::BUYER)->first()->amount)->toBe(7000.0);
});

test('a damaged-item complaint moves no money until the admin decides', function () {
    $order = ledgerOrder($buyer = User::factory()->create(), User::factory()->create(), 50000, 5000);

    OrderCancellationService::cancel($order->load('items.product'), $buyer, OrderCancellationReason::ITEM_DAMAGED, 'cracked');

    expect($order->fresh()->complaint_status)->toBe(OrderComplaintStatus::UNDER_REVIEW)
        ->and($order->transactions()->whereIn('type', [PaymentTransactionType::REFUND_ISSUED, PaymentTransactionType::DEPOSIT_FORFEITED, PaymentTransactionType::DEBT_RECORDED])->count())->toBe(0)
        ->and(PayoutRequest::count())->toBe(0);
});

test('filing a complaint notifies every admin, but a non-complaint cancellation does not', function () {
    Notification::fake();

    $admin = makeLedgerAdmin();
    $buyer = User::factory()->create();

    $complaintOrder = ledgerOrder($buyer, User::factory()->create(), 50000, 5000);
    OrderCancellationService::cancel($complaintOrder->load('items.product'), $buyer, OrderCancellationReason::ITEM_DAMAGED, 'cracked');

    Notification::assertSentTo($admin, ComplaintFiledNotification::class, fn ($n) => $n->order->id === $complaintOrder->id);

    $plainOrder = ledgerOrder($buyer, User::factory()->create(), 50000, 5000);
    OrderCancellationService::cancel($plainOrder->load('items.product'), $buyer, OrderCancellationReason::CHANGED_MIND, null);

    Notification::assertNotSentTo($admin, ComplaintFiledNotification::class, fn ($n) => $n->order->id === $plainOrder->id);
});

test('a confirmed-damaged verdict refunds the buyer in full, revokes the seller and issues a 30% penalty', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $order = ledgerOrder($buyer = User::factory()->create(), $seller, 50000, 12000);
    OrderCancellationService::cancel($order->load('items.product'), $buyer, OrderCancellationReason::NOT_AS_DESCRIBED, null);

    $penalty = OrderCancellationService::recordDamageVerdict($order->fresh(), makeLedgerAdmin(), true, 'Photos confirm it');

    $order->refresh();
    $seller->refresh();

    expect($order->complaint_status)->toBe(OrderComplaintStatus::CONFIRMED_DAMAGED)
        ->and($order->deposit_status)->toBe(OrderDepositStatus::REFUND_OWED)
        ->and((float) $order->transactions()->where('type', PaymentTransactionType::REFUND_ISSUED)->sum('amount'))->toBe(12000.0)
        ->and($order->payoutRequests()->where('recipient_role', PayoutRecipientRole::BUYER)->first()->amount)->toBe(12000.0)
        ->and($seller->is_seller)->toBeFalse()
        ->and($seller->is_auction_allowed)->toBeFalse()
        ->and($penalty->amount)->toBe(15000.0)
        ->and($penalty->prior_is_seller)->toBeTrue()
        ->and($penalty->prior_is_auction_allowed)->toBeTrue()
        ->and(SellerDebt::count())->toBe(0);
});

test('a not-damaged verdict falls back to the 80/20 forfeit rule and leaves the seller untouched', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $order = ledgerOrder($buyer = User::factory()->create(), $seller, 50000, 5000);
    OrderCancellationService::cancel($order->load('items.product'), $buyer, OrderCancellationReason::DOCUMENTS_MISSING, null);

    OrderCancellationService::recordDamageVerdict($order->fresh(), makeLedgerAdmin(), false, 'Documents were provided');

    expect($order->fresh()->deposit_status)->toBe(OrderDepositStatus::FORFEITED)
        ->and((float) $order->transactions()->where('type', PaymentTransactionType::DEPOSIT_FORFEITED)->sum('amount'))->toBe(5000.0)
        ->and($seller->fresh()->is_seller)->toBeTrue()
        ->and(SellerDebt::count())->toBe(0);
});

test('outstanding debt is deducted from the seller share of a forfeited deposit', function () {
    $seller = User::factory()->create();
    $earlier = ledgerOrder(User::factory()->create(), $seller, 50000, 5000);
    // Debt is seeded directly - nothing in the app creates a SellerDebt row
    // anymore (DamagePenaltyService's direct seller-pays-admin flow
    // superseded the old record()-based one), but recoverFromPayout() still
    // has to correctly deduct from whatever debt already exists.
    $debt = SellerDebt::factory()->create(['seller_id' => $seller->id, 'related_order_id' => $earlier->id, 'amount' => 25000]);

    $order = ledgerOrder($buyer = User::factory()->create(), $seller, 50000, 5000);
    OrderCancellationService::cancel($order->load('items.product'), $buyer, OrderCancellationReason::CHANGED_MIND, null);

    $payout = $order->payoutRequests()->where('recipient_role', PayoutRecipientRole::SELLER)->first();

    // The seller share is Rs. 1,000, wholly swallowed by the Rs. 25,000 debt: nothing to transfer.
    expect($payout->amount)->toBe(0.0)
        ->and($payout->debt_deducted)->toBe(1000.0)
        ->and($payout->payout_status)->toBe(PayoutStatus::SETTLED)
        ->and($debt->fresh()->remaining)->toBe(24000.0)
        ->and((float) $earlier->transactions()->where('type', PaymentTransactionType::DEBT_RECOVERED)->sum('amount'))->toBe(1000.0);
});

test('a payout larger than the debt clears it and pays the difference', function () {
    $seller = User::factory()->create();
    $debtOrder = ledgerOrder(User::factory()->create(), $seller, 1000, 100);
    $debt = SellerDebt::factory()->create(['seller_id' => $seller->id, 'related_order_id' => $debtOrder->id, 'amount' => 500]);

    $order = ledgerOrder(User::factory()->create(), $seller, 50000, 5000);

    $deducted = SellerDebtService::recoverFromPayout($seller, 1000, $order);

    expect($deducted)->toBe(500.0)
        ->and($debt->fresh()->remaining)->toBe(0.0)
        ->and($debt->fresh()->status)->toBe(SellerDebtStatus::RECOVERED);
});

test('the recipient submits payout details and the admin marks the transfer sent', function () {
    $order = ledgerOrder($buyer = User::factory()->create(), $seller = User::factory()->create(), 50000, 5000);
    OrderCancellationService::cancel($order->load('items.product'), $seller, null, null);
    $payout = $order->payoutRequests()->first();

    expect(OrderPaymentService::submitPayoutDetails($payout, $seller, 'X', '9800000000', null))->toBeFalse()
        ->and(OrderPaymentService::markSent($payout, makeLedgerAdmin()))->toBeFalse()
        ->and(OrderPaymentService::submitPayoutDetails($payout, $buyer, 'Buyer B', '9800000001', null))->toBeTrue()
        ->and(OrderPaymentService::markSent($payout->fresh(), makeLedgerAdmin()))->toBeTrue();

    expect($payout->fresh()->payout_status)->toBe(PayoutStatus::SENT)
        ->and((float) $order->transactions()->where('type', PaymentTransactionType::PAYOUT_SENT)->sum('amount'))->toBe(5000.0);
});
