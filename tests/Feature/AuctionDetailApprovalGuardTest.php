<?php

use App\Livewire\User\AuctionDetail;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function guardTestAuction(string $approvalStatus): Auction
{
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);

    $product = Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $sub->id,
        'name' => 'Unapproved Camera',
        'description' => 'x',
        'condition' => 'like-new',
        'quantity' => 1,
        'listing_type' => 'auction',
        'status' => $approvalStatus === 'approved' ? 'active' : 'pending',
        'approval_status' => $approvalStatus,
    ]);

    $auction = Auction::create([
        'product_id' => $product->id,
        'auction_type' => 'traditional',
        'start_time' => now()->addDay(),
        'end_time' => now()->addDays(2),
        'current_price' => 1000,
        'status' => $approvalStatus === 'approved' ? 'active' : 'pending',
    ]);

    TraditionalAuction::create([
        'auction_id' => $auction->id,
        'starting_bid' => 1000,
        'reserve_price' => 0,
        'min_bid_increment' => 100,
        'timer_start_seconds' => 60,
        'timer_reset_seconds' => 15,
    ]);

    return $auction;
}

test('a stranger gets a 404 visiting an auction whose listing is not approved', function () {
    $auction = guardTestAuction('pending');
    $stranger = User::factory()->create();

    Livewire::actingAs($stranger)
        ->test(AuctionDetail::class, ['auction' => $auction])
        ->assertStatus(404);
});

test('a guest gets a 404 visiting an auction whose listing is not approved', function () {
    $auction = guardTestAuction('pending');

    Livewire::test(AuctionDetail::class, ['auction' => $auction])
        ->assertStatus(404);
});

test('a stranger gets a 404 visiting an auction whose listing was rejected or unlisted', function () {
    $rejected = guardTestAuction('rejected');
    $unlisted = guardTestAuction('unlisted');
    $stranger = User::factory()->create();

    Livewire::actingAs($stranger)->test(AuctionDetail::class, ['auction' => $rejected])->assertStatus(404);
    Livewire::actingAs($stranger)->test(AuctionDetail::class, ['auction' => $unlisted])->assertStatus(404);
});

test('the owning seller can still preview their own auction before it is approved', function () {
    $auction = guardTestAuction('pending');
    $seller = $auction->product->user;

    Livewire::actingAs($seller)
        ->test(AuctionDetail::class, ['auction' => $auction])
        ->assertOk();
});

test('anyone can view an approved, live auction', function () {
    $auction = guardTestAuction('approved');
    $stranger = User::factory()->create();

    Livewire::actingAs($stranger)
        ->test(AuctionDetail::class, ['auction' => $auction])
        ->assertOk();

    $this->actingAs($stranger)
        ->get(route('user.auction.detail', $auction))
        ->assertOk();
});
