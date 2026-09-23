<?php

use App\Enums\ProductApprovalStatus;
use App\Livewire\Admin\ProductDetail as AdminProductDetail;
use App\Livewire\Admin\Products as AdminProducts;
use App\Livewire\User\ManageProduct;
use App\Livewire\User\ProductDetail;
use App\Livewire\User\Products;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function approvalTestProduct(User $seller, string $status = 'pending', ?string $remarks = null): Product
{
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);

    return Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $sub->id,
        'name' => 'Widget '.Str::random(4),
        'description' => 'x',
        'condition' => 'like-new',
        'quantity' => 1,
        'sale_price' => 1000,
        'listing_type' => 'direct_seller',
        'status' => 'pending',
        'approval_status' => $status,
        'remarks' => $remarks,
    ]);
}

function approvalTestAdmin(): Admin
{
    return Admin::create(['name' => 'A', 'email' => 'a'.Str::random(4).'@x.com', 'phone' => '98'.random_int(10000000, 99999999), 'password' => 'password', 'status' => 'active']);
}

test('a new product defaults to pending', function () {
    $product = approvalTestProduct(User::factory()->create(), 'pending');

    expect($product->approval_status)->toBe(ProductApprovalStatus::PENDING)
        ->and($product->approval_status_label)->toBe('Pending');
});

test('admin approving a pending product publishes it live', function () {
    $product = approvalTestProduct(User::factory()->create());
    $this->actingAs(approvalTestAdmin(), 'admin');

    Livewire::test(AdminProductDetail::class, ['product' => $product])
        ->call('approveProduct');

    expect($product->fresh())
        ->approval_status->toBe(ProductApprovalStatus::APPROVED)
        ->status->toBe('active');
});

test('admin rejecting a product requires a reason and locks it from editing', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = approvalTestProduct($seller);
    $this->actingAs(approvalTestAdmin(), 'admin');

    Livewire::test(AdminProducts::class)
        ->set('decidingProductId', $product->id)
        ->call('rejectProduct');
    expect($product->fresh()->approval_status)->toBe(ProductApprovalStatus::PENDING);

    Livewire::test(AdminProducts::class)
        ->set('decidingProductId', $product->id)
        ->set('decisionReason', 'Counterfeit item.')
        ->call('rejectProduct');

    expect($product->fresh())
        ->approval_status->toBe(ProductApprovalStatus::REJECTED)
        ->remarks->toBe('Counterfeit item.');

    Livewire::actingAs($seller)
        ->test(ManageProduct::class, ['product' => $product->fresh()])
        ->assertRedirect(route('user.products'));
});

test('a correction request lets the seller edit and resubmitting resets to pending', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = approvalTestProduct($seller);
    $this->actingAs(approvalTestAdmin(), 'admin');

    Livewire::test(AdminProducts::class)
        ->set('decidingProductId', $product->id)
        ->set('decisionReason', 'Please add more photos.')
        ->call('requestCorrection');

    $product->refresh();
    expect($product->approval_status)->toBe(ProductApprovalStatus::CORRECTION)
        ->and($product->remarks)->toBe('Please add more photos.')
        ->and($product->isEditableBySeller())->toBeTrue();

    ProductImage::create(['product_id' => $product->id, 'path' => 'products/existing.jpg', 'image_type' => 'general', 'sort_order' => 1]);
    ProductImage::create(['product_id' => $product->id, 'path' => 'products/proofs/existing.jpg', 'image_type' => 'proof', 'sort_order' => 1]);

    Livewire::actingAs($seller)
        ->test(ManageProduct::class, ['product' => $product])
        ->set('name', 'Updated Name')
        ->set('description', 'Updated description text.')
        ->set('sub_category_id', $product->sub_category_id)
        ->set('condition', 'like-new')
        ->set('quantity', 1)
        ->set('sale_price', 1200)
        ->set('negotiable', 'fixed')
        ->set('listing_type', 'direct_seller')
        ->set('meetup_location', 'Kathmandu')
        ->call('save');

    expect($product->fresh())
        ->approval_status->toBe(ProductApprovalStatus::PENDING)
        ->remarks->toBeNull()
        ->name->toBe('Updated Name');
});

test('a rejected product cannot be edited by its seller', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = approvalTestProduct($seller, 'rejected', 'Not allowed.');

    Livewire::actingAs($seller)
        ->test(ManageProduct::class, ['product' => $product])
        ->assertRedirect(route('user.products'));
});

test('a pending product (not yet reviewed) cannot be edited by its seller either', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = approvalTestProduct($seller, 'pending');

    Livewire::actingAs($seller)
        ->test(ManageProduct::class, ['product' => $product])
        ->assertRedirect(route('user.products'));
});

test('an approved product is view-only: the seller can see it but not edit it', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = approvalTestProduct($seller, 'approved');
    $product->update(['status' => 'active']);

    // View still works for the owner.
    Livewire::actingAs($seller)->test(ProductDetail::class, ['product' => $product])->assertOk();

    // Editing is blocked.
    Livewire::actingAs($seller)
        ->test(ManageProduct::class, ['product' => $product])
        ->assertRedirect(route('user.products'));
});

test('the owning seller can still view a pending or rejected product, but nobody else can', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $stranger = User::factory()->create();
    $product = approvalTestProduct($seller, 'rejected');

    Livewire::actingAs($seller)->test(ProductDetail::class, ['product' => $product])->assertOk();

    Livewire::actingAs($stranger)->test(ProductDetail::class, ['product' => $product])->assertStatus(404);
});

test('a seller cannot delete an already-approved product but can delete a rejected one', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $approved = approvalTestProduct($seller, 'approved');
    $rejected = approvalTestProduct($seller, 'rejected');

    $component = Livewire::actingAs($seller)->test(Products::class);
    $component->call('deleteProduct', $approved);
    expect(Product::find($approved->id))->not->toBeNull();

    $component->call('deleteProduct', $rejected);
    expect(Product::find($rejected->id))->toBeNull();
});

test('only approved products show on the public marketplace and homepage', function () {
    $seller = User::factory()->create();
    approvalTestProduct($seller, 'pending');
    $approved = approvalTestProduct($seller, 'approved');
    $approved->update(['status' => 'active']);

    $this->get(route('home'))->assertSee($approved->name);

    expect(Product::approved()->count())->toBe(1);
});
