<?php

use App\Events\AuctionEnded;
use App\Livewire\User\AuctionDetail;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use App\Services\AuctionEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeLiveAuction(int $endsInSeconds = 60, float $reserve = 1000): Auction
{
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $category = Category::create(['name' => 'Cameras', 'slug' => 'cameras-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);
    $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'DSLR', 'slug' => 'dslr-'.Str::random(5), 'status' => 'active', 'sort_order' => 1]);

    $product = Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $sub->id,
        'name' => 'Settlement Test Camera',
        'description' => 'Test',
        'condition' => 'like-new',
        'quantity' => 1,
        'listing_type' => 'auction',
        'status' => 'active',
        'is_approved' => true,
    ]);

    $auction = Auction::create([
        'product_id' => $product->id,
        'auction_type' => 'traditional',
        'start_time' => now()->subHour(),
        'end_time' => now()->addSeconds($endsInSeconds),
        'current_price' => 500,
        'status' => 'active',
    ]);

    TraditionalAuction::create([
        'auction_id' => $auction->id,
        'starting_bid' => 500,
        'reserve_price' => $reserve,
        'min_bid_increment' => 100,
        'timer_start_seconds' => 60,
        'timer_reset_seconds' => 15,
    ]);

    return $auction;
}

test('the countdown can settle an auction the moment its time is up and the page shows the winner', function () {
    Mail::fake();
    Notification::fake();
    Event::fake([AuctionEnded::class]);

    $auction = makeLiveAuction(60);
    $winner = User::factory()->create(['name' => 'Winning Wanda', 'is_auction_allowed' => true]);
    Bid::create(['auction_id' => $auction->id, 'bidder_id' => $winner->id, 'bid_amount' => 1500, 'ip_address' => '127.0.0.1', 'placed_at' => '10:00:00']);

    $this->actingAs($winner);
    $component = Livewire::test(AuctionDetail::class, ['auction' => $auction]);

    // Still live when the page was opened; no result is shown yet.
    $component->assertDontSee('Winning Bidder');

    $this->travel(2)->minutes();

    $component->call('finalizeIfEnded')
        ->assertSee('Winning Bidder')
        ->assertSee('Winning Wanda')
        ->assertDontSee('Item Unsold');

    expect($auction->fresh()->status)->toBe('completed')
        ->and($auction->fresh()->winner_id)->toBe($winner->id)
        ->and(Order::count())->toBe(1);

    Event::assertDispatched(AuctionEnded::class, fn (AuctionEnded $event): bool => $event->auction->id === $auction->id);
});

test('an auction with no admissible bid is announced as unsold without a refresh', function () {
    Mail::fake();
    Notification::fake();

    $auction = makeLiveAuction(60, reserve: 5000);
    $viewer = User::factory()->create(['is_auction_allowed' => true]);
    Bid::create(['auction_id' => $auction->id, 'bidder_id' => $viewer->id, 'bid_amount' => 700, 'ip_address' => '127.0.0.1', 'placed_at' => '10:00:00']);

    $this->actingAs($viewer);
    $component = Livewire::test(AuctionDetail::class, ['auction' => $auction]);

    $this->travel(2)->minutes();

    $component->call('finalizeIfEnded')->assertSee('Item Unsold');

    expect($auction->fresh()->status)->toBe('ended_unsold')
        ->and(Order::count())->toBe(0);
});

test('time being up is not the same as being settled', function () {
    $auction = makeLiveAuction(-30);

    expect($auction->isEnded())->toBeTrue()
        ->and($auction->isSettled())->toBeFalse();

    $auction->update(['status' => 'completed']);

    expect($auction->fresh()->isSettled())->toBeTrue();
});

test('two viewers settling the same auction create only one order', function () {
    Mail::fake();
    Notification::fake();

    $auction = makeLiveAuction(-30);
    $bidder = User::factory()->create(['is_auction_allowed' => true]);
    Bid::create(['auction_id' => $auction->id, 'bidder_id' => $bidder->id, 'bid_amount' => 1500, 'ip_address' => '127.0.0.1', 'placed_at' => '10:00:00']);

    // Two separate, equally stale in-memory copies, as two open browser tabs would have.
    $first = Auction::find($auction->id);
    $second = Auction::find($auction->id);

    expect(AuctionEngineService::checkAndFinalizeIfExpired($first))->toBeTrue()
        ->and(AuctionEngineService::checkAndFinalizeIfExpired($second))->toBeFalse()
        ->and(Order::count())->toBe(1);
});

test('a stale copy is not settled after a late bid extended the deadline', function () {
    $auction = makeLiveAuction(-30);
    $stale = Auction::find($auction->id);

    // Meanwhile a last-second bid pushed the real deadline into the future.
    Auction::whereKey($auction->id)->update(['extended_end_time' => now()->addSeconds(15)]);

    expect(AuctionEngineService::checkAndFinalizeIfExpired($stale))->toBeFalse()
        ->and($auction->fresh()->status)->toBe('active');
});

test('when two bidders have the same proxy ceiling the one who registered it first wins, matching the live leader', function () {
    Mail::fake();
    Notification::fake();

    $auction = makeLiveAuction(3600, reserve: 1000);
    $auction->update(['current_price' => 2000]);
    $auction->traditionalAuction->update(['starting_bid' => 2000, 'min_bid_increment' => 100]);

    $early = User::factory()->create(['name' => 'Early Eli', 'is_auction_allowed' => true]);
    $late = User::factory()->create(['name' => 'Late Lata', 'is_auction_allowed' => true]);

    // Eli sets a secret ceiling of 3,000 first; Lata later bids exactly 3,000 openly.
    AuctionEngineService::processBid($auction->fresh(), $early, 2100, 3000);
    AuctionEngineService::processBid($auction->fresh(), $late, 3000);

    $live = $auction->fresh();
    expect((float) $live->current_price)->toBe(3000.0);

    $this->travel(2)->hours();

    $result = AuctionEngineService::determineWinner($auction->fresh());

    // Eli's ceiling was registered first, so he is the leader Lata sees live and he must win.
    expect($result['winner_id'])->toBe($early->id)
        ->and($result['winning_price'])->toBe(3000.0)
        ->and(Order::first()->buyer_id)->toBe($early->id);
});
