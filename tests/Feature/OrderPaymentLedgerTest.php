<?php

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\SellerDebt;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\EsewaPaymentService;
use App\Services\OrderCancellationService;
use App\Services\OrderPaymentService;
use App\Services\SellerDebtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function ledgerOrder(User $buyer, User $seller, float $total = 50000, float $paidOnline = 5000): Order
{
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Thing', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => $total, 'listing_type' => 'direct_seller',
        'status' => 'active', 'is_approved' => true,
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
        ->and($order->transactions()->where('type', 'balance_paid_cash')->count())->toBe(0);
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

    expect((float) $order->transactions()->where('status', 'pending')->sum('amount'))->toBe(20000.0);
});

test('changing your mind forfeits only the minimum deposit, split 80/20, and refunds the excess', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = ledgerOrder($buyer, $seller, 50000, 12000);

    OrderCancellationService::cancel($order->load('items.product'), $buyer, 'changed_mind', null);

    $forfeits = $order->transactions()->where('type', 'deposit_forfeited')->get();

    expect((float) $forfeits->sum('amount'))->toBe(5000.0)
        ->and((float) $forfeits->firstWhere('party', 'admin')->amount)->toBe(4000.0)
        ->and((float) $forfeits->firstWhere('party', 'seller')->amount)->toBe(1000.0)
        ->and((float) $order->transactions()->where('type', 'refund_issued')->sum('amount'))->toBe(7000.0)
        ->and($order->fresh()->deposit_status)->toBe('forfeited')
        ->and($order->payoutRequests()->where('recipient_role', 'seller')->first()->amount)->toBe(1000.0)
        ->and($order->payoutRequests()->where('recipient_role', 'buyer')->first()->amount)->toBe(7000.0);
});

test('a damaged-item complaint moves no money until the admin decides', function () {
    $order = ledgerOrder($buyer = User::factory()->create(), User::factory()->create(), 50000, 5000);

    OrderCancellationService::cancel($order->load('items.product'), $buyer, 'item_damaged', 'cracked');

    expect($order->fresh()->complaint_status)->toBe('under_review')
        ->and($order->transactions()->whereIn('type', ['refund_issued', 'deposit_forfeited', 'debt_recorded'])->count())->toBe(0)
        ->and(PayoutRequest::count())->toBe(0);
});

test('an upheld complaint refunds the buyer in full and records the seller debt of half the price', function () {
    $order = ledgerOrder($buyer = User::factory()->create(), $seller = User::factory()->create(), 50000, 12000);
    OrderCancellationService::cancel($order->load('items.product'), $buyer, 'not_as_described', null);

    OrderCancellationService::resolveComplaint($order->fresh(), makeLedgerAdmin(), true, 'Photos confirm it');

    $order->refresh();

    expect($order->complaint_status)->toBe('upheld')
        ->and($order->deposit_status)->toBe('refund_owed')
        ->and((float) $order->transactions()->where('type', 'refund_issued')->sum('amount'))->toBe(12000.0)
        ->and(SellerDebtService::outstandingFor($seller))->toBe(25000.0)
        ->and($order->payoutRequests()->where('recipient_role', 'buyer')->first()->amount)->toBe(37000.0);
});

test('a rejected complaint falls back to the 80/20 forfeit rule', function () {
    $order = ledgerOrder($buyer = User::factory()->create(), User::factory()->create(), 50000, 5000);
    OrderCancellationService::cancel($order->load('items.product'), $buyer, 'documents_missing', null);

    OrderCancellationService::resolveComplaint($order->fresh(), makeLedgerAdmin(), false, 'Documents were provided');

    expect($order->fresh()->deposit_status)->toBe('forfeited')
        ->and((float) $order->transactions()->where('type', 'deposit_forfeited')->sum('amount'))->toBe(5000.0)
        ->and(SellerDebt::count())->toBe(0);
});

test('outstanding debt is deducted from the seller share of a forfeited deposit', function () {
    $seller = User::factory()->create();
    $earlier = ledgerOrder(User::factory()->create(), $seller, 50000, 5000);
    SellerDebtService::record($earlier, 'test');

    $order = ledgerOrder($buyer = User::factory()->create(), $seller, 50000, 5000);
    OrderCancellationService::cancel($order->load('items.product'), $buyer, 'changed_mind', null);

    $payout = $order->payoutRequests()->where('recipient_role', 'seller')->first();

    // The seller share is Rs. 1,000, wholly swallowed by the Rs. 25,000 debt: nothing to transfer.
    expect($payout->amount)->toBe(0.0)
        ->and($payout->debt_deducted)->toBe(1000.0)
        ->and($payout->payout_status)->toBe(PayoutRequest::STATUS_SETTLED)
        ->and(SellerDebtService::outstandingFor($seller))->toBe(24000.0)
        ->and((float) $earlier->transactions()->where('type', 'debt_recovered')->sum('amount'))->toBe(1000.0);
});

test('a payout larger than the debt clears it and pays the difference', function () {
    $seller = User::factory()->create();
    $debtOrder = ledgerOrder(User::factory()->create(), $seller, 1000, 100);
    SellerDebtService::record($debtOrder, 'small');

    $order = ledgerOrder(User::factory()->create(), $seller, 50000, 5000);

    $deducted = SellerDebtService::recoverFromPayout($seller, 1000, $order);

    expect($deducted)->toBe(500.0)
        ->and(SellerDebtService::outstandingFor($seller))->toBe(0.0)
        ->and(SellerDebt::first()->status)->toBe('recovered');
});

test('the recipient submits payout details and the admin marks the transfer sent', function () {
    $order = ledgerOrder($buyer = User::factory()->create(), $seller = User::factory()->create(), 50000, 5000);
    OrderCancellationService::cancel($order->load('items.product'), $seller, null, null);
    $payout = $order->payoutRequests()->first();

    expect(OrderPaymentService::submitPayoutDetails($payout, $seller, 'X', '9800000000', null))->toBeFalse()
        ->and(OrderPaymentService::markSent($payout, makeLedgerAdmin()))->toBeFalse()
        ->and(OrderPaymentService::submitPayoutDetails($payout, $buyer, 'Buyer B', '9800000001', null))->toBeTrue()
        ->and(OrderPaymentService::markSent($payout->fresh(), makeLedgerAdmin()))->toBeTrue();

    expect($payout->fresh()->payout_status)->toBe('sent')
        ->and((float) $order->transactions()->where('type', 'payout_sent')->sum('amount'))->toBe(5000.0);
});
