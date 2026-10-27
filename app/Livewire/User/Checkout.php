<?php

namespace App\Livewire\User;

use App\Enums\OrderPaymentStatus;
use App\Enums\ProductApprovalStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\NewOrderReceivedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;
use RuntimeException;
use Throwable;

#[Layout('layouts.app')]
class Checkout extends Component
{
    use Toast;

    private const HANDOVER_TYPE = 'meetup';

    private const PAYMENT_METHOD = 'cash_on_meetup';

    public ?int $directProductId = null;

    // Untyped (not `int`): a strictly-typed property throws when the buyer
    // clears the number input (Livewire sends "" for wire:model.live), which
    // crashes the whole component instead of just failing validation. See
    // ProductDetail::$quantity for the same fix.
    public mixed $quantity = 1;

    public string $meetup_location = '';

    public string $meetup_date_np = '';

    public string $meetup_date_en = '';

    public string $meetup_time_of_day = '';

    public string $buyer_phone = '';

    public string $notes = '';

    public function mount(?int $product = null): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        $this->buyer_phone = $user->phone ?? '';

        if ($product) {
            $targetProduct = Product::find($product);
            if ($targetProduct && (int) $targetProduct->seller_id === (int) $user->id) {
                $this->error('You cannot purchase your own product listing.');
                $this->redirect(route('user.products.show', $targetProduct->slug), navigate: true);

                return;
            }

            if ($targetProduct && $targetProduct->isDirectSell()) {
                // Closes the gap ProductDetail's own view guard doesn't cover:
                // this route can be reached directly (an old link, a stale
                // tab, a guessed/typed id) without ever passing through the
                // product page's own approval/live check, so it has to be
                // repeated here too - not just relied on for placeOrder()'s
                // server-side check further down.
                if ($targetProduct->approval_status !== ProductApprovalStatus::APPROVED || $targetProduct->status !== 'active') {
                    $this->error('This product is no longer available for purchase.');
                    $this->redirect(route('home'), navigate: true);

                    return;
                }

                $this->directProductId = $targetProduct->id;
                $this->meetup_location = $targetProduct->meetup_location ?: ($targetProduct->location ?? '');

                $requestedQuantity = (int) request()->query('quantity', 1);
                $this->quantity = max(1, min($requestedQuantity, $targetProduct->stock_quantity));
            }
        } else {
            // Default meetup location from first item in cart
            $firstCartItem = $user->cartItems()->with('product')->first();
            if ($firstCartItem && $firstCartItem->product) {
                $this->meetup_location = $firstCartItem->product->meetup_location ?: ($firstCartItem->product->location ?? '');
            }
        }
    }

    /**
     * Live stock check as the buyer adjusts the direct-buy quantity on this
     * page - mirrors ProductDetail::updatedQuantity().
     */
    public function updatedQuantity(): void
    {
        $this->resetErrorBag('quantity');

        if (! $this->directProductId) {
            return;
        }

        $product = Product::find($this->directProductId);
        if (! $product) {
            return;
        }

        if (! is_numeric($this->quantity) || (int) $this->quantity < 1) {
            $this->addError('quantity', 'Please enter a quantity of at least 1.');
        } elseif ((int) $this->quantity > $product->stock_quantity) {
            $this->addError('quantity', 'Only '.$product->stock_quantity.' unit(s) available in stock.');
        }
    }

    public function placeOrder(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->validate([
            'buyer_phone' => 'required|string|max:20',
            'notes' => 'nullable|string',
            'meetup_location' => 'required|string|max:255',
            'meetup_date_np' => 'required|string|max:20',
            'meetup_date_en' => 'required|date|after_or_equal:today',
            'meetup_time_of_day' => 'required|date_format:H:i',
        ], [
            'meetup_date_np.required' => 'Please pick a meetup date.',
            'meetup_date_en.required' => 'Please pick a valid meetup date.',
            'meetup_date_en.after_or_equal' => 'The meetup date cannot be in the past.',
            'meetup_time_of_day.required' => 'Please pick a meetup time.',
            'meetup_time_of_day.date_format' => 'Please pick a valid meetup time.',
        ]);

        // Fetch checkout items
        if ($this->directProductId) {
            $product = Product::findOrFail($this->directProductId);

            if (! is_numeric($this->quantity) || (int) $this->quantity < 1) {
                $this->addError('quantity', 'Please enter a quantity of at least 1.');

                return;
            }

            $checkoutGroups = collect([
                [
                    'seller_id' => $product->seller_id,
                    'items' => [
                        [
                            'product' => $product,
                            'quantity' => (int) $this->quantity,
                            'price' => (float) $product->sale_price,
                        ],
                    ],
                ],
            ]);
        } else {
            $cartItems = $user->cartItems()->with('product.user')->get();
            if ($cartItems->isEmpty()) {
                $this->error('Your cart is empty.');
                $this->redirect(route('user.cart'), navigate: true);

                return;
            }

            // Group cart items by seller
            $grouped = $cartItems->groupBy(fn (CartItem $item) => $item->product->seller_id);
            $checkoutGroups = $grouped->map(fn ($items, $sellerId) => [
                'seller_id' => $sellerId,
                'items' => $items->map(fn (CartItem $item) => [
                    'product' => $item->product,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->product->sale_price,
                ])->all(),
            ])->values();
        }

        try {
            $createdOrders = [];

            DB::transaction(function () use ($user, $checkoutGroups, &$createdOrders): void {
                // Defense in depth: quantity and live-listing status were
                // already checked client-side (ProductDetail/Cart/mount()
                // here) for the direct-buy case, but either can change
                // between then and now - another buyer, a tampered request,
                // or the seller unlisting the product while it sat in
                // someone's cart or a stale product page - so every item is
                // re-checked here against a row-locked read of the product
                // immediately before the order is created.
                foreach ($checkoutGroups as $group) {
                    foreach ($group['items'] as $itemData) {
                        /** @var Product $product */
                        $product = $itemData['product'];
                        $locked = Product::whereKey($product->id)->lockForUpdate()->first();

                        if (! $locked || $locked->approval_status !== ProductApprovalStatus::APPROVED || $locked->status !== 'active') {
                            throw new RuntimeException(
                                "\"{$product->name}\" is no longer available for purchase - it may have been unlisted or is still awaiting approval."
                            );
                        }

                        if ($itemData['quantity'] > $locked->stock_quantity) {
                            throw new RuntimeException(
                                "Only {$locked->stock_quantity} unit(s) of \"{$product->name}\" are in stock - please adjust the quantity and try again."
                            );
                        }
                    }
                }

                foreach ($checkoutGroups as $group) {
                    $sellerId = $group['seller_id'];
                    $itemsData = $group['items'];

                    $totalAmount = array_reduce($itemsData, function ($sum, $item) {
                        return $sum + ($item['price'] * $item['quantity']);
                    }, 0.0);

                    $order = Order::create([
                        'buyer_id' => $user->id,
                        'seller_id' => $sellerId,
                        'status' => 'pending',
                        'total_amount' => $totalAmount,
                        'payment_method' => self::PAYMENT_METHOD,
                        'payment_status' => OrderPaymentStatus::PENDING,
                        'handover_type' => self::HANDOVER_TYPE,
                        'meetup_location' => $this->meetup_location,
                        'meetup_time' => Carbon::parse("{$this->meetup_date_en} {$this->meetup_time_of_day}"),
                        'meetup_time_np' => "{$this->meetup_date_np} {$this->meetup_time_of_day}",
                        'buyer_phone' => $this->buyer_phone,
                        'notes' => $this->notes,
                    ]);

                    foreach ($itemsData as $itemData) {
                        /** @var Product $product */
                        $product = $itemData['product'];
                        $qty = $itemData['quantity'];
                        $price = $itemData['price'];

                        $order->items()->create([
                            'product_id' => $product->id,
                            'price' => $price,
                            'quantity' => $qty,
                            'subtotal' => $price * $qty,
                        ]);

                        // Log Product Timeline
                        $product->logTimeline(
                            'ordered',
                            "Order Placed (#{$order->order_number})",
                            "Order for {$qty} unit(s) placed by buyer {$user->name}. Handover: in-person meetup, cash on handover.",
                            $user
                        );

                        // If direct buy, remove from cart
                        CartItem::where('user_id', $user->id)
                            ->where('product_id', $product->id)
                            ->delete();
                    }

                    $createdOrders[] = $order;
                }
            });

            foreach ($createdOrders as $order) {
                try {
                    $order->seller?->notify(new NewOrderReceivedNotification($order));
                } catch (Throwable $e) {
                    report($e);
                }
            }

            $this->success('Order placed successfully! The seller has been notified.');
            $this->redirect(route('user.orders'), navigate: true);

        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
        } catch (Throwable $e) {
            $this->error('An error occurred while placing order: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        $user = Auth::user();
        if ($this->directProductId) {
            $product = Product::with(['images', 'user', 'category'])->find($this->directProductId);
            // While the buyer is mid-edit (field briefly empty), display 0
            // rather than letting a non-numeric value reach arithmetic below.
            $displayQuantity = is_numeric($this->quantity) ? (int) $this->quantity : 0;
            $items = $product ? collect([[
                'product' => $product,
                'quantity' => $displayQuantity,
                'price' => (float) $product->sale_price,
                'subtotal' => (float) $product->sale_price * $displayQuantity,
            ]]) : collect();
        } else {
            $items = $user
                ? $user->cartItems()
                    ->with('product.images', 'product.user')
                    ->get()
                    ->map(fn (CartItem $item) => [
                        'product' => $item->product,
                        'quantity' => $item->quantity,
                        'price' => (float) ($item->product->sale_price ?? 0),
                        'subtotal' => (float) ($item->product->sale_price ?? 0) * $item->quantity,
                    ])
                : collect();
        }

        $totalAmount = $items->sum('subtotal');

        return view('livewire.user.checkout', [
            'checkoutItems' => $items,
            'totalAmount' => $totalAmount,
        ]);
    }
}
