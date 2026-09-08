<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('home page renders for guests with products', function () {
    $seller = User::factory()->create(['is_seller' => true]);
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

    Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $subCategory->id,
        'name' => 'Sample Laptop',
        'description' => 'Test',
        'condition' => 'lightly-used',
        'quantity' => 1,
        'sale_price' => 50000,
        'listing_type' => 'direct_seller',
        'status' => 'active',
        'is_approved' => true,
        'is_featured' => true,
        'is_trending' => true,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Sample Laptop')
        ->assertSee('Add Sample Laptop to cart', false);
});
