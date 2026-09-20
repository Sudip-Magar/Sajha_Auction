<?php

namespace Tests\Feature;

use App\Livewire\User\Checkout;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Notifications\NewOrderReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutMeetupOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(User $seller, bool $delivery): Product
    {
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'status' => 'active', 'sort_order' => 1]);
        $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Laptops', 'slug' => 'laptops', 'status' => 'active', 'sort_order' => 1]);

        return Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'sub_category_id' => $sub->id,
            'name' => 'Second Hand Laptop',
            'description' => 'Good condition',
            'condition' => 'good',
            'quantity' => 1,
            'sale_price' => 30000,
            'listing_type' => 'direct_seller',
            'negotiable' => 'fixed',
            'meetup_location' => 'New Road',
            'delivery_available' => $delivery,
            'status' => 'active',
            'is_approved' => true,
        ]);
    }

    public function test_meetup_only_product_rejects_delivery_and_non_cash_payment(): void
    {
        Notification::fake();
        $buyer = User::factory()->create();
        $product = $this->makeProduct(User::factory()->create(['is_seller' => true]), false);
        $this->actingAs($buyer);

        Livewire::test(Checkout::class, ['product' => $product->id])
            ->set('handover_type', 'delivery')
            ->set('shipping_address', 'Somewhere')
            ->set('buyer_phone', '9800000000')
            ->call('placeOrder');

        Livewire::test(Checkout::class, ['product' => $product->id])
            ->set('payment_method', 'esewa')
            ->set('meetup_location', 'New Road')
            ->set('buyer_phone', '9800000000')
            ->call('placeOrder');

        $this->assertDatabaseCount('orders', 0);
        Notification::assertNothingSent();
    }

    public function test_meetup_only_order_succeeds_notifies_seller_and_stores_bs_and_ad_time(): void
    {
        Notification::fake();
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_seller' => true]);
        $product = $this->makeProduct($seller, false);
        $this->actingAs($buyer);

        Livewire::test(Checkout::class, ['product' => $product->id])
            ->set('meetup_location', 'New Road')
            ->set('buyer_phone', '9800000000')
            ->set('meetup_date_np', '2083-12-30')
            ->set('meetup_date_en', now()->addDays(3)->toDateString())
            ->set('meetup_time_of_day', '14:30')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'seller_id' => $seller->id,
            'payment_method' => 'cash_on_meetup',
            'handover_type' => 'meetup',
            'meetup_time_np' => '2083-12-30 14:30',
        ]);

        Notification::assertSentTo($seller, NewOrderReceivedNotification::class);
    }

    public function test_delivery_enabled_product_allows_delivery(): void
    {
        Notification::fake();
        $buyer = User::factory()->create();
        $product = $this->makeProduct(User::factory()->create(['is_seller' => true]), true);
        $this->actingAs($buyer);

        Livewire::test(Checkout::class, ['product' => $product->id])
            ->set('handover_type', 'delivery')
            ->set('payment_method', 'cash_on_delivery')
            ->set('shipping_address', 'Somewhere 12')
            ->set('buyer_phone', '9800000000')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('orders', ['handover_type' => 'delivery', 'payment_method' => 'cash_on_delivery']);
    }

    public function test_time_without_date_fails_validation(): void
    {
        $buyer = User::factory()->create();
        $product = $this->makeProduct(User::factory()->create(['is_seller' => true]), false);
        $this->actingAs($buyer);

        Livewire::test(Checkout::class, ['product' => $product->id])
            ->set('meetup_location', 'New Road')
            ->set('buyer_phone', '9800000000')
            ->set('meetup_time_of_day', '14:30')
            ->call('placeOrder')
            ->assertHasErrors(['meetup_date_en']);
    }
}
