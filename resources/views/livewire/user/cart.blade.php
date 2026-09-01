<div class="min-h-screen bg-gray-50/50 py-10 dark:bg-[#101114]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="border-b border-gray-200 pb-5 dark:border-gray-800">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                <x-icon name="o-shopping-cart" class="w-8 h-8 text-[#1F6F5F]" />
                Shopping Cart
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Review items, quantities, and meetup details before placing your order
            </p>
        </div>

        @if($cartItems->isEmpty())
            <div class="mt-12 rounded-3xl border border-dashed border-gray-300 bg-white p-12 text-center shadow-xs dark:border-gray-800 dark:bg-[#181A1F]">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/30">
                    <x-icon name="o-shopping-bag" class="h-8 w-8 text-[#2FA084]" />
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Your cart is currently empty</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Explore direct sell second-hand products and add them to your cart!
                </p>
                <div class="mt-6">
                    <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-[#1F6F5F] to-[#2FA084] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#2FA084]/20 hover:opacity-95">
                        <x-icon name="o-arrow-left" class="w-4 h-4" />
                        Explore Marketplace
                    </a>
                </div>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-12">
                {{-- Cart Items List --}}
                <div class="lg:col-span-8 space-y-4">
                    @foreach($cartItems as $item)
                        @php
                            $product = $item->product;
                            $mainImg = $product?->image ? Storage::url($product->image) : null;
                            $price = (float) ($product?->sale_price ?? 0);
                            $itemSubtotal = $price * $item->quantity;
                        @endphp
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-[#181A1F]">
                            <div class="flex items-center gap-4 min-w-0">
                                {{-- Thumbnail --}}
                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                                    @if($mainImg)
                                        <img src="{{ $mainImg }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full items-center justify-center text-gray-400">
                                            <x-icon name="o-photo" class="h-8 w-8" />
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        Direct Sell
                                    </span>
                                    <h3 class="mt-1 font-bold text-gray-900 truncate dark:text-white">
                                        <a href="{{ route('user.products.show', $product->slug) }}" wire:navigate class="hover:underline">
                                            {{ $product->name }}
                                        </a>
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Seller: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $product->user?->name ?? 'Seller' }}</span>
                                    </p>

                                    @if($product->meetup_location || $product->location)
                                        <p class="mt-1 flex items-center gap-1 text-xs text-emerald-700 dark:text-emerald-400 font-medium">
                                            <x-icon name="o-map-pin" class="w-3.5 h-3.5" />
                                            Meetup: {{ $product->meetup_location ?: $product->location }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between w-full sm:w-auto gap-6 border-t sm:border-t-0 pt-3 sm:pt-0 border-gray-100 dark:border-gray-800">
                                {{-- Quantity Selector --}}
                                <div class="flex items-center rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800">
                                    <button type="button"
                                            wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-white font-bold text-gray-700 shadow-xs hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-200">
                                        -
                                    </button>
                                    <span class="w-8 text-center text-sm font-extrabold text-gray-900 dark:text-white">
                                        {{ $item->quantity }}
                                    </span>
                                    <button type="button"
                                            wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-white font-bold text-gray-700 shadow-xs hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-200">
                                        +
                                    </button>
                                </div>

                                {{-- Price & Subtotal --}}
                                <div class="text-right">
                                    <p class="text-base font-black text-gray-900 dark:text-white">
                                        Rs {{ number_format($itemSubtotal) }}
                                    </p>
                                    <p class="text-[11px] text-gray-400">
                                        Rs {{ number_format($price) }} each
                                    </p>
                                </div>

                                {{-- Remove --}}
                                <button type="button"
                                        wire:click="removeItem({{ $item->id }})"
                                        class="text-gray-400 hover:text-rose-500 transition-colors p-1"
                                        title="Remove Item">
                                    <x-icon name="o-trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Order Summary Sidebar --}}
                <div class="lg:col-span-4">
                    <div class="sticky top-24 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-[#181A1F]">
                        <h2 class="text-xl font-black text-gray-900 dark:text-white border-b border-gray-100 pb-4 dark:border-gray-800">
                            Order Summary
                        </h2>

                        <div class="mt-4 space-y-3">
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>Subtotal ({{ $cartItems->sum('quantity') }} items)</span>
                                <span class="font-bold text-gray-900 dark:text-white">Rs {{ number_format($subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>Handover Option</span>
                                <span class="font-bold text-emerald-600">Free Meetup / Handover</span>
                            </div>
                            <div class="border-t border-gray-100 pt-3 dark:border-gray-800 flex justify-between text-lg font-black text-gray-900 dark:text-white">
                                <span>Total Amount</span>
                                <span class="text-[#1F6F5F] dark:text-[#7CE0C5]">Rs {{ number_format($subtotal) }}</span>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <a href="{{ route('user.checkout') }}" wire:navigate
                               class="w-full flex items-center justify-center gap-2 rounded-xl bg-linear-to-r from-[#1F6F5F] to-[#2FA084] py-3.5 text-sm font-extrabold text-white shadow-lg shadow-[#2FA084]/20 hover:opacity-95 transition-all">
                                <x-icon name="o-check-circle" class="w-5 h-5" />
                                Proceed to Checkout
                            </a>

                            <a href="{{ route('home') }}" wire:navigate
                               class="w-full flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white py-3 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
