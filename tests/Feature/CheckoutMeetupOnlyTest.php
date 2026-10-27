<?php

namespace Tests\Feature;

use App\Livewire\User\Checkout;
use App\Livewire\User\OrderDetail;
use App\Livewire\User\Orders;
use App\Models\Category;
use App\Models\Order;
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

    private function makeProduct(User $seller): Product
    {
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'status' => 'active', 'sort_order' => 1]);
        $sub = SubCategory::create(['category_id' => $category->id, 'name' => 'Laptops', 'slug' => 'laptops', 'status' => 'active', 'sort_order' => 1]);

        return Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'sub_category_id' => $sub->id,
            'name' => 'Second Hand Laptop',
            'description' => 'Good condition',
            'condition' => 'lightly-used',
            'quantity' => 1,
            'sale_price' => 30000,
            'listing_type' => 'direct_seller',
            'negotiable' => 'fixed',
            'meetup_location' => 'New Road',
            'status' => 'active',
            'approval_status' => 'approved',
        ]);
    }

    private function fillValidCheckout($component)
    {
        return $component
            ->set('meetup_location', 'New Road')
            ->set('buyer_phone', '9800000000')
            ->set('meetup_date_np', '2083-12-30')
            ->set('meetup_date_en', now()->addDays(3)->toDateString())
            ->set('meetup_time_of_day', '14:30');
    }

    public function test_order_is_always_an_in_person_meetup_paid_in_cash_and_notifies_the_seller(): void
    {
        Notification::fake();
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_seller' => true]);
        $product = $this->makeProduct($seller);
        $this->actingAs($buyer);

        $this->fillValidCheckout(Livewire::test(Checkout::class, ['product' => $product->id]))
            ->call('placeOrder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'seller_id' => $seller->id,
            'payment_method' => 'cash_on_meetup',
            'handover_type' => 'meetup',
            'shipping_address' => null,
            'meetup_time_np' => '2083-12-30 14:30',
        ]);

        Notification::assertSentTo($seller, NewOrderReceivedNotification::class);
    }

    public function test_handover_and_payment_cannot_be_chosen_by_the_client(): void
    {
        $buyer = User::factory()->create();
        $product = $this->makeProduct(User::factory()->create(['is_seller' => true]));
        $this->actingAs($buyer);

        $component = Livewire::test(Checkout::class, ['product' => $product->id]);

        $this->assertFalse(property_exists($component->instance(), 'handover_type'));
        $this->assertFalse(property_exists($component->instance(), 'payment_method'));
        $this->assertFalse(property_exists($component->instance(), 'shipping_address'));
    }

    public function test_checkout_page_only_offers_meetup_and_cash(): void
    {
        $buyer = User::factory()->create();
        $product = $this->makeProduct(User::factory()->create(['is_seller' => true]));
        $this->actingAs($buyer);

        Livewire::test(Checkout::class, ['product' => $product->id])
            ->assertSee('In-Person Meetup')
            ->assertSee('Cash on Meetup / Handover')
            ->assertDontSee('Delivery')
            ->assertDontSee('Khalti')
            ->assertDontSee('eSewa')
            ->assertDontSee('(Optional)');
    }

    public function test_meetup_date_and_time_are_required(): void
    {
        Notification::fake();
        $buyer = User::factory()->create();
        $product = $this->makeProduct(User::factory()->create(['is_seller' => true]));
        $this->actingAs($buyer);

        Livewire::test(Checkout::class, ['product' => $product->id])
            ->set('meetup_location', 'New Road')
            ->set('buyer_phone', '9800000000')
            ->call('placeOrder')
            ->assertHasErrors(['meetup_date_np', 'meetup_date_en', 'meetup_time_of_day']);

        $this->assertDatabaseCount('orders', 0);
        Notification::assertNothingSent();
    }

    public function test_time_without_date_fails_validation(): void
    {
        $buyer = User::factory()->create();
        $product = $this->makeProduct(User::factory()->create(['is_seller' => true]));
        $this->actingAs($buyer);

        Livewire::test(Checkout::class, ['product' => $product->id])
            ->set('meetup_location', 'New Road')
            ->set('buyer_phone', '9800000000')
            ->set('meetup_time_of_day', '14:30')
            ->call('placeOrder')
            ->assertHasErrors(['meetup_date_en']);
    }

    public function test_meetup_date_cannot_be_in_the_past(): void
    {
        $buyer = User::factory()->create();
        $product = $this->makeProduct(User::factory()->create(['is_seller' => true]));
        $this->actingAs($buyer);

        $this->fillValidCheckout(Livewire::test(Checkout::class, ['product' => $product->id]))
            ->set('meetup_date_en', now()->subDay()->toDateString())
            ->call('placeOrder')
            ->assertHasErrors(['meetup_date_en']);
    }

    public function test_orders_pages_show_the_meetup_date_and_time(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['is_seller' => true]);
        $product = $this->makeProduct($seller);
        $this->actingAs($buyer);

        $this->fillValidCheckout(Livewire::test(Checkout::class, ['product' => $product->id]))
            ->call('placeOrder');

        $order = Order::firstOrFail();
        $date = $order->meetup_time->format('M d, Y');

        Livewire::test(Orders::class)
            ->assertSee('Meetup Date:')
            ->assertSee($date)
            ->assertSee('Meetup Time:')
            ->assertSee('02:30 PM')
            ->assertSee('2083-12-30 B.S.');

        Livewire::test(OrderDetail::class, ['order' => $order])
            ->assertSee('Meetup Date:')
            ->assertSee($date)
            ->assertSee('Meetup Time:')
            ->assertSee('02:30 PM')
            ->assertSee('In-Person Meetup')
            ->assertSee('Cash on Meetup / Handover');
    }
}
