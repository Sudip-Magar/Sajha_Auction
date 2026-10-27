<?php

use App\Livewire\User\Dashboard;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function dashboardProduct(User $seller, string $approvalStatus): Product
{
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);

    return Product::create([
        'seller_id' => $seller->id, 'sub_category_id' => $sub->id, 'name' => 'Thing '.Str::random(4), 'description' => 'x',
        'condition' => 'like-new', 'quantity' => 1, 'sale_price' => 1000, 'listing_type' => 'direct_seller',
        'status' => 'active', 'approval_status' => $approvalStatus,
    ]);
}

test('needs-attention count includes pending, correction, and rejected products, not just pending', function () {
    $seller = User::factory()->create(['is_seller' => true]);

    dashboardProduct($seller, 'pending');
    dashboardProduct($seller, 'correction');
    dashboardProduct($seller, 'rejected');
    dashboardProduct($seller, 'approved');

    Livewire::actingAs($seller)
        ->test(Dashboard::class)
        ->assertViewHas('needsAttentionProducts', 3);
});
