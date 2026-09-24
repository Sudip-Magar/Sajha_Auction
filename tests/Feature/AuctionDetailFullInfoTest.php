<?php

use App\Livewire\User\AuctionDetail;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function fullInfoAuction(): Auction
{
    Storage::fake('public');

    $seller = User::factory()->create(['name' => 'Info Seller', 'phone' => '9800011122', 'is_seller' => true, 'is_auction_allowed' => true]);
    $category = Category::create(['name' => 'Cameras', 'slug' => 'cameras-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'DSLR', 'slug' => 'dslr-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);

    $product = Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $sub->id,
        'name' => 'Full Info Camera',
        'description' => 'A well cared for camera.',
        'condition' => 'like-new',
        'usage_duration' => '6 months',
        'quantity' => 1,
        'listing_type' => 'auction',
        'status' => 'active',
        'approval_status' => 'approved',
    ]);
    $product->proofImages()->create(['path' => 'products/proofs/receipt.jpg', 'image_type' => 'proof', 'sort_order' => 1]);
    $product->logTimeline('uploaded', 'Product Uploaded for Review', 'Listed by seller.', $seller);

    $auction = Auction::create([
        'product_id' => $product->id,
        'auction_type' => 'traditional',
        'start_time' => now()->subHour(),
        'end_time' => now()->addHour(),
        'current_price' => 5000,
        'status' => 'active',
    ]);

    TraditionalAuction::create([
        'auction_id' => $auction->id,
        'starting_bid' => 5000,
        'reserve_price' => 0,
        'min_bid_increment' => 100,
        'timer_start_seconds' => 60,
        'timer_reset_seconds' => 15,
    ]);

    return $auction;
}

test('the live auction page shows full product info, matching the product detail page', function () {
    $auction = fullInfoAuction();
    $viewer = User::factory()->create(['is_auction_allowed' => true]);

    Livewire::actingAs($viewer)
        ->test(AuctionDetail::class, ['auction' => $auction])
        // Badges & quick stats
        ->assertSee('views')
        ->assertSee('Condition: Like New')
        ->assertSee('Used: 6 months')
        ->assertSee('DSLR')
        // Proof of authenticity
        ->assertSee('Proof of Authenticity')
        // Product lifecycle & timeline
        ->assertSee('Product Lifecycle', false)
        ->assertSee('Product Uploaded for Review')
        // Seller details
        ->assertSee('Seller Details')
        ->assertSee('Info Seller')
        ->assertSee('9800011122')
        // Redesigned price stats
        ->assertSee('Current Standing Price')
        ->assertSee('Rs. 5,000.00')
        ->assertSee('Min. Bid Increase')
        ->assertSee('Lot Size');
});
