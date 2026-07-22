<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Livewire\Livewire;
use Tests\TestCase;

class DirectSellMarketplaceTest extends TestCase
{
    public function test_user_can_add_product_to_wishlist_and_remove_it(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['is_seller' => true]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'name' => 'Second Hand iPhone 12',
            'description' => 'Great condition',
            'condition' => 'like-new',
            'quantity' => 1,
            'sale_price' => 45000,
            'listing_type' => 'direct_seller',
            'negotiable' => 'fixed',
            'meetup_location' => 'Koteshwor Chowk, Kathmandu',
            'status' => 'active',
            'is_approved' => true,
        ]);

        $this->actingAs($user);

        Livewire::test('user.wishlist')
            ->call('addToCart', $product->id);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        Livewire::test('user.wishlist')
            ->call('removeFromWishlist', $product->id);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_user_can_place_direct_sell_order_with_meetup_location(): void
    {
        $buyer = User::factory()->create(['phone' => '9800000000']);
        $seller = User::factory()->create(['is_seller' => true]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'name' => 'Used Sony Headphones',
            'description' => 'Noise canceling, light use',
            'condition' => 'lightly-used',
            'quantity' => 1,
            'sale_price' => 12000,
            'listing_type' => 'direct_seller',
            'negotiable' => 'fixed',
            'meetup_location' => 'New Road Complex, Kathmandu',
            'status' => 'active',
            'is_approved' => true,
        ]);

        CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($buyer);

        Livewire::test('user.checkout')
            ->set('handover_type', 'meetup')
            ->set('meetup_location', 'New Road Complex, Kathmandu')
            ->set('payment_method', 'cash_on_meetup')
            ->set('buyer_phone', '9800000000')
            ->call('placeOrder')
            ->assertRedirect(route('user.orders'));

        $this->assertDatabaseHas('orders', [
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'status' => 'pending',
            'meetup_location' => 'New Road Complex, Kathmandu',
            'total_amount' => 12000,
        ]);

        $order = Order::first();

        $this->assertDatabaseHas('product_timelines', [
            'product_id' => $product->id,
            'event_type' => 'ordered',
        ]);
    }
}
