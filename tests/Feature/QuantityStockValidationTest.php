<?php

use App\Livewire\User\Cart;
use App\Livewire\User\Checkout;
use App\Livewire\User\ManageProduct;
use App\Livewire\User\ProductDetail;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\TraditionalAuction;
use App\Models\User;
use App\Notifications\AuctionMultiUnitListedNotification;
use App\Services\AuctionEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function stockTestSubCategory(): SubCategory
{
    $category = Category::create([
        'name' => 'Electronics',
        'slug' => 'electronics-'.Str::random(5),
        'status' => 'active',
        'sort_order' => 1,
    ]);

    return SubCategory::create([
        'category_id' => $category->id,
        'name' => 'Cameras',
        'slug' => 'cameras-'.Str::random(5),
        'status' => 'active',
        'sort_order' => 1,
    ]);
}

function stockTestProduct(User $seller, int $quantity, string $listingType = 'direct_seller'): Product
{
    return Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => stockTestSubCategory()->id,
        'name' => 'Stock Test Camera',
        'description' => 'Good condition',
        'condition' => 'lightly-used',
        'quantity' => $quantity,
        'sale_price' => 5000,
        'listing_type' => $listingType,
        'negotiable' => 'fixed',
        'meetup_location' => 'New Road',
        'status' => 'active',
        'approval_status' => 'approved',
    ]);
}

// ---- Step 2: direct-sell quantity validation ----

test('product detail page live-validates quantity against stock as it changes', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 3);
    $buyer = User::factory()->create();

    Livewire::actingAs($buyer)
        ->test(ProductDetail::class, ['product' => $product])
        ->set('quantity', 5)
        ->assertHasErrors(['quantity'])
        ->set('quantity', 3)
        ->assertHasNoErrors(['quantity']);
});

test('clearing the quantity field does not crash the product detail page', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 3);
    $buyer = User::factory()->create();

    // Livewire sends "" (not null or 0) when a wire:model.live number input
    // is cleared in the browser - this used to throw PropertyNotFoundException
    // because $quantity was strictly typed `int`.
    Livewire::actingAs($buyer)
        ->test(ProductDetail::class, ['product' => $product])
        ->set('quantity', '')
        ->assertOk()
        ->assertHasErrors(['quantity'])
        ->call('addToCart')
        ->assertHasErrors(['quantity']);

    expect(CartItem::where('product_id', $product->id)->exists())->toBeFalse();
});

test('clearing the quantity field does not crash the checkout page', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 5);
    $buyer = User::factory()->create();

    Livewire::actingAs($buyer)->test(Checkout::class, ['product' => $product->id])
        ->set('quantity', '')
        ->assertOk()
        ->assertHasErrors(['quantity'])
        ->set('meetup_location', 'New Road')
        ->set('buyer_phone', '9800000000')
        ->set('meetup_date_np', '2083-12-30')
        ->set('meetup_date_en', now()->addDays(3)->toDateString())
        ->set('meetup_time_of_day', '14:30')
        ->call('placeOrder')
        ->assertHasErrors(['quantity']);

    expect(Order::count())->toBe(0);
});

test('add to cart is rejected when the requested quantity exceeds stock', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 3);
    $buyer = User::factory()->create();

    Livewire::actingAs($buyer)
        ->test(ProductDetail::class, ['product' => $product])
        ->set('quantity', 10)
        ->call('addToCart')
        ->assertHasErrors(['quantity']);

    expect(CartItem::where('user_id', $buyer->id)->where('product_id', $product->id)->exists())->toBeFalse();
});

test('add to cart is rejected when combined with an existing cart quantity it would exceed stock', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 3);
    $buyer = User::factory()->create();

    CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 2]);

    Livewire::actingAs($buyer)
        ->test(ProductDetail::class, ['product' => $product])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertHasErrors(['quantity']);

    expect(CartItem::where('user_id', $buyer->id)->where('product_id', $product->id)->first()->quantity)->toBe(2);
});

test('buy now redirects with the chosen quantity when it is within stock', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 5);
    $buyer = User::factory()->create();

    Livewire::actingAs($buyer)
        ->test(ProductDetail::class, ['product' => $product])
        ->set('quantity', 3)
        ->call('buyNow')
        ->assertRedirect(route('user.checkout', ['product' => $product->id, 'quantity' => 3]));
});

test('buy now is blocked when the chosen quantity exceeds stock', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 2);
    $buyer = User::factory()->create();

    Livewire::actingAs($buyer)
        ->test(ProductDetail::class, ['product' => $product])
        ->set('quantity', 4)
        ->call('buyNow')
        ->assertHasErrors(['quantity'])
        ->assertNoRedirect();
});

test('cart quantity cannot be pushed past available stock', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 3);
    $buyer = User::factory()->create();
    $cartItem = CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 3]);

    Livewire::actingAs($buyer)
        ->test(Cart::class)
        ->call('updateQuantity', $cartItem->id, 10);

    expect($cartItem->fresh()->quantity)->toBe(3);
});

test('checkout rejects a direct-buy order when stock drops below the requested quantity before placing it', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 5);
    $buyer = User::factory()->create();

    $component = Livewire::actingAs($buyer)->test(Checkout::class, ['product' => $product->id])
        ->set('quantity', 5)
        ->set('meetup_location', 'New Road')
        ->set('buyer_phone', '9800000000')
        ->set('meetup_date_np', '2083-12-30')
        ->set('meetup_date_en', now()->addDays(3)->toDateString())
        ->set('meetup_time_of_day', '14:30');

    // Stock drops (another buyer, or a race) between the page loading and
    // this buyer confirming - the server-side check must still catch it.
    $product->update(['quantity' => 2]);

    $component->call('placeOrder');

    expect(Order::count())->toBe(0);
});

test('a real visit to the checkout page picks up the quantity chosen on the product page from the URL', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 5);
    $buyer = User::factory()->create();

    $response = $this->actingAs($buyer)->get(route('user.checkout', ['product' => $product->id, 'quantity' => 3]));

    $response->assertOk()->assertSee('Rs 15,000');
});

test('checkout creates a direct-buy order with the chosen quantity when stock allows it', function () {
    Notification::fake();
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 5);
    $buyer = User::factory()->create();

    Livewire::actingAs($buyer)->test(Checkout::class, ['product' => $product->id])
        ->set('quantity', 3)
        ->set('meetup_location', 'New Road')
        ->set('buyer_phone', '9800000000')
        ->set('meetup_date_np', '2083-12-30')
        ->set('meetup_date_en', now()->addDays(3)->toDateString())
        ->set('meetup_time_of_day', '14:30')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::firstOrFail();
    expect($order->items()->first())
        ->quantity->toBe(3)
        ->subtotal->toBe(15000.0);
});

test('checkout rejects a cart-based order when a cart line now exceeds stock', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $product = stockTestProduct($seller, 5);
    $buyer = User::factory()->create();
    CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 5]);

    // Simulate the product's stock having dropped since the cart item was
    // added (bypassing the Cart page's own clamp entirely).
    $product->update(['quantity' => 1]);

    Livewire::actingAs($buyer)->test(Checkout::class)
        ->set('meetup_location', 'New Road')
        ->set('buyer_phone', '9800000000')
        ->set('meetup_date_np', '2083-12-30')
        ->set('meetup_date_en', now()->addDays(3)->toDateString())
        ->set('meetup_time_of_day', '14:30')
        ->call('placeOrder');

    expect(Order::count())->toBe(0);
});

// ---- Step 3: auction full-lot winner ----

test('the auction winner receives the entire listed lot, not one unit', function () {
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $product = stockTestProduct($seller, 4, 'auction');

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

    $bidder = User::factory()->create(['is_auction_allowed' => true]);

    Bid::create([
        'auction_id' => $auction->id,
        'bidder_id' => $bidder->id,
        'bid_amount' => 8000,
        'ip_address' => '127.0.0.1',
        'placed_at' => now(),
    ]);

    AuctionEngineService::determineWinner($auction);

    $order = Order::where('auction_id', $auction->id)->firstOrFail();
    $item = $order->items()->firstOrFail();

    expect($item->quantity)->toBe(4)
        ->and($item->subtotal)->toBe(8000.0)
        ->and($item->price)->toBe(2000.0)
        ->and($order->total_amount)->toBe(8000.0);
});

test('a new auction listing with quantity 1 does not notify the seller about a multi-unit lot', function () {
    Notification::fake();
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);

    Livewire::actingAs($seller)
        ->test(ManageProduct::class, ['type' => 'auction'])
        ->set('name', 'Single Unit Camera')
        ->set('description', 'A fine camera.')
        ->set('sub_category_id', stockTestSubCategory()->id)
        ->set('condition', 'like-new')
        ->set('quantity', 1)
        ->set('negotiable', 'fixed')
        ->set('newImages', [UploadedFile::fake()->image('camera.jpg')])
        ->set('newProofImages', [UploadedFile::fake()->image('receipt.jpg')])
        ->set('auction_start_date_en', now()->addDay()->toDateString())
        ->set('auction_start_time', '10:00')
        ->set('auction_start_np', '2083-01-01')
        ->set('auction_end_date_en', now()->addDays(2)->toDateString())
        ->set('auction_end_time', '10:00')
        ->set('auction_end_np', '2083-01-02')
        ->set('starting_bid', 1000)
        ->call('save');

    Notification::assertNotSentTo($seller, AuctionMultiUnitListedNotification::class);
});

test('a new auction listing with quantity greater than 1 notifies the seller that the whole lot goes to one winner', function () {
    Notification::fake();
    $seller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);

    Livewire::actingAs($seller)
        ->test(ManageProduct::class, ['type' => 'auction'])
        ->set('name', 'Multi Unit Camera Lot')
        ->set('description', 'Five identical cameras.')
        ->set('sub_category_id', stockTestSubCategory()->id)
        ->set('condition', 'like-new')
        ->set('quantity', 5)
        ->set('negotiable', 'fixed')
        ->set('newImages', [UploadedFile::fake()->image('camera.jpg')])
        ->set('newProofImages', [UploadedFile::fake()->image('receipt.jpg')])
        ->set('auction_start_date_en', now()->addDay()->toDateString())
        ->set('auction_start_time', '10:00')
        ->set('auction_start_np', '2083-01-01')
        ->set('auction_end_date_en', now()->addDays(2)->toDateString())
        ->set('auction_end_time', '10:00')
        ->set('auction_end_np', '2083-01-02')
        ->set('starting_bid', 1000)
        ->call('save');

    Notification::assertSentTo($seller, AuctionMultiUnitListedNotification::class);
});

// ---- Step 4: display ----

test('the product detail page shows stock for direct-sell and lot size for auctions', function () {
    $seller = User::factory()->create(['is_seller' => true]);
    $direct = stockTestProduct($seller, 7);
    $viewer = User::factory()->create();

    Livewire::actingAs($viewer)
        ->test(ProductDetail::class, ['product' => $direct])
        ->assertSee('7 unit(s) in stock');

    $auctionSeller = User::factory()->create(['is_seller' => true, 'is_auction_allowed' => true]);
    $auctionProduct = stockTestProduct($auctionSeller, 4, 'auction');
    Auction::create([
        'product_id' => $auctionProduct->id,
        'auction_type' => 'traditional',
        'start_time' => now()->subHour(),
        'end_time' => now()->addHour(),
        'current_price' => 5000,
        'status' => 'active',
    ]);

    Livewire::actingAs($viewer)
        ->test(ProductDetail::class, ['product' => $auctionProduct])
        ->assertSee('Lot size: 4 unit(s)')
        ->assertSee('the winner takes the entire lot');
});
