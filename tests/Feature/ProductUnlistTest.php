<?php

use App\Enums\ProductApprovalStatus;
use App\Livewire\User\Checkout;
use App\Livewire\User\ManageProduct;
use App\Livewire\User\ProductDetail;
use App\Livewire\User\Products;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function unlistTestSubCategory(): SubCategory
{
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);

    return SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
}

function unlistTestProduct(User $seller, string $approvalStatus = 'approved', string $listingType = 'direct_seller'): Product
{
    return Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => unlistTestSubCategory()->id,
        'name' => 'Lenovo LOQ',
        'description' => 'x',
        'condition' => 'like-new',
        'quantity' => 5,
        'sale_price' => 80000,
        'listing_type' => $listingType,
        'negotiable' => 'fixed',
        'status' => 'active',
        'approval_status' => $approvalStatus,
    ]);
}

test('a seller can unlist an approved direct-sell product without deleting it', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = unlistTestProduct($seller);

    Livewire::actingAs($seller)
        ->test(Products::class)
        ->call('unlistProduct', $product);

    $product->refresh();
    expect($product->approval_status)->toBe(ProductApprovalStatus::UNLISTED);
    expect(Product::find($product->id))->not->toBeNull();
});

test('unlisting is refused for a product that is not currently approved', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = unlistTestProduct($seller, 'pending');

    Livewire::actingAs($seller)
        ->test(Products::class)
        ->call('unlistProduct', $product);

    expect($product->fresh()->approval_status)->toBe(ProductApprovalStatus::PENDING);
});

test('unlisting is refused for an auction listing', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $product = unlistTestProduct($seller, 'approved', 'auction');
    $auction = Auction::create([
        'product_id' => $product->id, 'auction_type' => 'traditional',
        'start_time' => now()->subHour(), 'end_time' => now()->addHour(),
        'current_price' => 1000, 'status' => 'active',
    ]);
    TraditionalAuction::create([
        'auction_id' => $auction->id, 'starting_bid' => 1000, 'reserve_price' => 0,
        'min_bid_increment' => 100, 'timer_start_seconds' => 60, 'timer_reset_seconds' => 15,
    ]);

    Livewire::actingAs($seller)
        ->test(Products::class)
        ->call('unlistProduct', $product);

    expect($product->fresh()->approval_status)->toBe(ProductApprovalStatus::APPROVED);
});

test('another seller cannot unlist someone else\'s product', function () {
    $owner = User::factory()->create(['is_seller' => true]);
    $stranger = User::factory()->create(['is_seller' => true]);
    $product = unlistTestProduct($owner);

    Livewire::actingAs($stranger)
        ->test(Products::class)
        ->call('unlistProduct', $product)
        ->assertStatus(403);
});

test('an unlisted product disappears from the product detail page for everyone but the owner', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = unlistTestProduct($seller);
    $product->unlistBySeller();

    $stranger = User::factory()->create();
    Livewire::actingAs($stranger)->test(ProductDetail::class, ['product' => $product])->assertStatus(404);

    Livewire::actingAs($seller)->test(ProductDetail::class, ['product' => $product])->assertOk();
});

test('an unlisted product is editable and resubmitting sends it back to pending', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = unlistTestProduct($seller);
    $product->unlistBySeller();

    expect($product->fresh()->isEditableBySeller())->toBeTrue();

    Livewire::actingAs($seller)
        ->test(ManageProduct::class, ['product' => $product->fresh()])
        ->assertOk();
});

test('checkout refuses to place a direct-buy order for a product the seller unlisted', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = unlistTestProduct($seller);
    $product->unlistBySeller();
    $buyer = User::factory()->create();

    Livewire::actingAs($buyer)
        ->test(Checkout::class, ['product' => $product->id])
        ->assertRedirect(route('home'));

    expect(Order::count())->toBe(0);
});

test('checkout refuses a cart-based order when the product was unlisted after being added to the cart', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = unlistTestProduct($seller);
    $buyer = User::factory()->create();
    $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

    // Unlisted only after it was already sitting in the buyer's cart.
    $product->unlistBySeller();

    Livewire::actingAs($buyer)->test(Checkout::class)
        ->set('meetup_location', 'New Road')
        ->set('buyer_phone', '9800000000')
        ->set('meetup_date_np', '2083-12-30')
        ->set('meetup_date_en', now()->addDays(3)->toDateString())
        ->set('meetup_time_of_day', '14:30')
        ->call('placeOrder');

    expect(Order::count())->toBe(0);
});
