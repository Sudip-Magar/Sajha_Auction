<?php

use App\Livewire\Home;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createBookmarksCleanupSubCategory(): SubCategory
{
    $category = Category::create([
        'name' => 'Electronics',
        'slug' => 'electronics-'.Str::random(5),
        'status' => 'active',
        'sort_order' => 1,
    ]);

    return SubCategory::create([
        'category_id' => $category->id,
        'name' => 'Laptops',
        'slug' => 'laptops-'.Str::random(5),
        'status' => 'active',
        'sort_order' => 1,
    ]);
}

test('the bookmarks table no longer exists', function () {
    expect(Schema::hasTable('bookmarks'))->toBeFalse();
});

test('toggling the wishlist from Home only touches the wishlists table', function () {
    $user = User::factory()->create();
    $seller = User::factory()->create(['is_seller' => true]);
    $subCategory = createBookmarksCleanupSubCategory();
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

    Livewire::actingAs($user)
        ->test(Home::class)
        ->call('toggleWishlist', $product->id);

    expect(Wishlist::where('user_id', $user->id)->where('product_id', $product->id)->exists())->toBeTrue();

    Livewire::actingAs($user)
        ->test(Home::class)
        ->call('toggleBookmark', $product->id);

    expect(Wishlist::where('user_id', $user->id)->where('product_id', $product->id)->exists())->toBeFalse();
});

test('the direct-sell products marketplace page renders and links to the product', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $subCategory = createBookmarksCleanupSubCategory();
    $product = Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $subCategory->id,
        'name' => 'Marketplace Test Item',
        'description' => 'Test',
        'condition' => 'new',
        'quantity' => 1,
        'sale_price' => 25000,
        'listing_type' => 'direct_seller',
        'status' => 'active',
        'is_approved' => true,
    ]);

    $this->get(route('user.marketplace-products'))
        ->assertOk()
        ->assertSee('Marketplace Test Item')
        ->assertSee(route('user.products.show', $product->slug), false);
});
