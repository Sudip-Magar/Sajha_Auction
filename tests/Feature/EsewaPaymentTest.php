<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\EsewaPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeDepositOrder(User $buyer, User $seller): Order
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
        'status' => 'pending',
        'total_amount' => 50000,
        'deposit_amount' => 5000,
        'deposit_status' => 'pending',
        'payment_method' => 'cash_on_meetup',
        'payment_status' => 'pending',
        'handover_type' => 'meetup',
        'buyer_phone' => '9800000000',
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'price' => 50000,
        'quantity' => 1,
        'subtotal' => 50000,
    ]);

    return $order;
}

test('build payment form produces a valid HMAC-SHA256 signature over the documented field order', function () {
    config(['services.esewa.product_code' => 'EPAYTEST', 'services.esewa.secret_key' => '8gBm/:&EnhH.1/q']);

    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = makeDepositOrder($buyer, $seller);

    $fields = (new EsewaPaymentService)->buildPaymentForm($order);

    expect($fields['total_amount'])->toBe('5000.00')
        ->and($fields['amount'])->toBe('5000.00')
        ->and($fields['product_code'])->toBe('EPAYTEST')
        ->and($fields['signed_field_names'])->toBe('total_amount,transaction_uuid,product_code');

    $expectedMessage = "total_amount={$fields['total_amount']},transaction_uuid={$fields['transaction_uuid']},product_code={$fields['product_code']}";
    $expectedSignature = base64_encode(hash_hmac('sha256', $expectedMessage, '8gBm/:&EnhH.1/q', true));

    expect($fields['signature'])->toBe($expectedSignature);

    $order->refresh();
    expect($order->deposit_transaction_uuid)->toBe($fields['transaction_uuid']);
});

test('decodeAndVerifyCallback accepts a correctly signed payload and rejects a tampered one', function () {
    config(['services.esewa.secret_key' => '8gBm/:&EnhH.1/q']);

    $payload = [
        'transaction_code' => 'ABC123',
        'status' => 'COMPLETE',
        'total_amount' => '5000.00',
        'transaction_uuid' => (string) Str::uuid(),
        'product_code' => 'EPAYTEST',
        'signed_field_names' => 'transaction_code,status,total_amount,transaction_uuid,product_code',
    ];

    $message = collect(explode(',', $payload['signed_field_names']))
        ->map(fn ($field) => "{$field}={$payload[$field]}")
        ->implode(',');
    $payload['signature'] = base64_encode(hash_hmac('sha256', $message, '8gBm/:&EnhH.1/q', true));

    $encoded = base64_encode(json_encode($payload));

    $service = new EsewaPaymentService;

    expect($service->decodeAndVerifyCallback($encoded))->toMatchArray(['status' => 'COMPLETE']);

    $tampered = $payload;
    $tampered['total_amount'] = '1.00';
    $tamperedEncoded = base64_encode(json_encode($tampered));

    expect($service->decodeAndVerifyCallback($tamperedEncoded))->toBeNull();
});

test('buyer can load the eSewa redirect page for their own pending-deposit order', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = makeDepositOrder($buyer, $seller);

    $this->actingAs($buyer)
        ->get(route('payment.esewa.initiate', $order))
        ->assertOk()
        ->assertSee(config('services.esewa.form_url'), false)
        ->assertSee('transaction_uuid', false);
});

test('a different user cannot initiate a deposit payment for someone else\'s order', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $stranger = User::factory()->create();
    $order = makeDepositOrder($buyer, $seller);

    $this->actingAs($stranger)
        ->get(route('payment.esewa.initiate', $order))
        ->assertForbidden();
});

test('success callback marks the deposit paid only after the status API also confirms COMPLETE', function () {
    config(['services.esewa.secret_key' => '8gBm/:&EnhH.1/q', 'services.esewa.status_url' => 'https://rc.esewa.com.np/api/epay/transaction/status/']);

    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = makeDepositOrder($buyer, $seller);
    $order->update(['deposit_transaction_uuid' => (string) Str::uuid()]);

    $payload = [
        'transaction_code' => 'ABC123',
        'status' => 'COMPLETE',
        'total_amount' => '5000.00',
        'transaction_uuid' => $order->deposit_transaction_uuid,
        'product_code' => 'EPAYTEST',
        'signed_field_names' => 'transaction_code,status,total_amount,transaction_uuid,product_code',
    ];
    $message = collect(explode(',', $payload['signed_field_names']))
        ->map(fn ($field) => "{$field}={$payload[$field]}")
        ->implode(',');
    $payload['signature'] = base64_encode(hash_hmac('sha256', $message, '8gBm/:&EnhH.1/q', true));
    $encoded = base64_encode(json_encode($payload));

    Http::fake([
        'rc.esewa.com.np/*' => Http::response(['status' => 'COMPLETE']),
    ]);

    $this->actingAs($buyer)
        ->get(route('payment.esewa.success', ['data' => $encoded]))
        ->assertRedirect(route('user.orders.show', $order));

    expect($order->fresh()->deposit_status)->toBe('paid');
});

test('success callback does not mark deposit paid if the independent status check disagrees', function () {
    config(['services.esewa.secret_key' => '8gBm/:&EnhH.1/q', 'services.esewa.status_url' => 'https://rc.esewa.com.np/api/epay/transaction/status/']);

    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $order = makeDepositOrder($buyer, $seller);
    $order->update(['deposit_transaction_uuid' => (string) Str::uuid()]);

    $payload = [
        'transaction_code' => 'ABC123',
        'status' => 'COMPLETE',
        'total_amount' => '5000.00',
        'transaction_uuid' => $order->deposit_transaction_uuid,
        'product_code' => 'EPAYTEST',
        'signed_field_names' => 'transaction_code,status,total_amount,transaction_uuid,product_code',
    ];
    $message = collect(explode(',', $payload['signed_field_names']))
        ->map(fn ($field) => "{$field}={$payload[$field]}")
        ->implode(',');
    $payload['signature'] = base64_encode(hash_hmac('sha256', $message, '8gBm/:&EnhH.1/q', true));
    $encoded = base64_encode(json_encode($payload));

    Http::fake([
        'rc.esewa.com.np/*' => Http::response(['status' => 'PENDING']),
    ]);

    $this->actingAs($buyer)
        ->get(route('payment.esewa.success', ['data' => $encoded]));

    expect($order->fresh()->deposit_status)->toBe('pending');
});
