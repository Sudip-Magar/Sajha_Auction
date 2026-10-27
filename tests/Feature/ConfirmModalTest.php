<?php

use App\Enums\ProductApprovalStatus;
use App\Livewire\Admin\CategorySetup;
use App\Livewire\Admin\ComplaintDetail;
use App\Livewire\User\Products;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function confirmModalAdmin(): Admin
{
    return Admin::create([
        'name' => 'Admin User',
        'email' => 'cm-'.Str::random(5).'@example.com',
        'phone' => '98'.random_int(10000000, 99999999),
        'password' => 'password',
        'status' => 'active',
    ]);
}

test('deleting a category goes through the confirm modal instead of deleting immediately', function () {
    $admin = confirmModalAdmin();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);

    $component = Livewire::actingAs($admin, 'admin')->test(CategorySetup::class);

    $component->call('confirmDeleteCategory', $category)
        ->assertSet('showDeleteCategoryModal', true)
        ->assertSet('confirmingCategoryId', $category->id);

    // Not deleted yet - only the modal opened.
    expect(Category::find($category->id))->not->toBeNull();

    $component->call('runConfirmedCategoryDelete')
        ->assertSet('showDeleteCategoryModal', false);

    expect(Category::find($category->id))->toBeNull();
});

test('unlisting and deleting a product each open their own confirm modal', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Camera', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 1000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);

    $component = Livewire::actingAs($seller)->test(Products::class);

    $component->call('confirmUnlistProduct', $product)
        ->assertSet('showUnlistProductModal', true);
    expect($product->fresh()->approval_status)->toBe(ProductApprovalStatus::APPROVED);

    $component->call('runConfirmedUnlist')
        ->assertSet('showUnlistProductModal', false);
    expect($product->fresh()->approval_status)->toBe(ProductApprovalStatus::UNLISTED);

    $component->call('confirmDeleteProduct', $product)
        ->assertSet('showDeleteProductModal', true);
    expect(Product::find($product->id))->not->toBeNull();

    $component->call('runConfirmedProductDelete')
        ->assertSet('showDeleteProductModal', false);
    expect(Product::find($product->id))->toBeNull();
});

test('recording a damage verdict and restoring access each go through their own confirm modal', function () {
    $admin = confirmModalAdmin();
    $buyer = User::factory()->create();
    $seller = User::factory()->create(['is_seller' => true]);
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $product = Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Camera', 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 20000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => 'approved',
    ]);
    $order = Order::create([
        'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'status' => 'cancelled', 'total_amount' => 20000,
        'deposit_amount' => 2000, 'deposit_status' => 'paid', 'payment_method' => 'cash_on_meetup', 'handover_type' => 'meetup',
        'cancellation_reason_category' => 'item_damaged', 'complaint_status' => 'under_review',
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 20000, 'subtotal' => 20000]);
    $order->transactions()->create(['type' => 'deposit_paid', 'amount' => 2000, 'payment_method' => 'esewa', 'status' => 'completed', 'party' => 'admin']);

    $component = Livewire::actingAs($admin, 'admin')->test(ComplaintDetail::class, ['order' => $order]);

    $component->call('confirmRecordVerdict', true)
        ->assertSet('showVerdictModal', true)
        ->assertSet('confirmingVerdict', true);

    // Not resolved yet - only the modal opened.
    expect($order->fresh()->complaint_status->value)->toBe('under_review');

    $component->call('runConfirmedVerdict')
        ->assertSet('showVerdictModal', false);

    expect($order->fresh()->complaint_status->value)->toBe('confirmed_damaged');
    expect($seller->fresh()->is_seller)->toBeFalse();

    $penalty = $order->fresh()->damagePenalty;
    $penalty->update(['status' => 'paid']);

    $component->call('confirmRestoreSellerAccess')
        ->assertSet('showRestoreAccessModal', true);

    $component->call('runConfirmedRestoreAccess')
        ->assertSet('showRestoreAccessModal', false);

    expect($seller->fresh()->is_seller)->toBeTrue();
});
