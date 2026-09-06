<div class="min-h-screen bg-gray-50/50 py-10 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 pb-5 dark:border-gray-800">
            <div>
                <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                    <x-icon name="o-heart" class="w-8 h-8 text-rose-500" />
                    My Wishlist
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Saved items you are interested in buying or tracking
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center gap-3">
                <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">
                    {{ $products->count() }} {{ str('Item')->plural($products->count()) }} Saved
                </span>
                <a href="{{ route('home') }}" wire:navigate class="text-sm font-semibold text-[#1F6F5F] hover:underline flex items-center gap-1">
                    <x-icon name="o-shopping-bag" class="w-4 h-4" />
                    Explore Marketplace
                </a>
            </div>
        </div>

        @if($products->isEmpty())
            <div class="mt-12 rounded-3xl border border-dashed border-gray-300 bg-white p-12 text-center shadow-xs dark:border-gray-800 dark:bg-[#181A1F]">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/30">
                    <x-icon name="o-heart" class="h-8 w-8 text-rose-400" />
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Your wishlist is empty</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Save second-hand items or auctions you like by clicking the heart icon on any product.
                </p>
                <div class="mt-6">
                    <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-[#1F6F5F] to-[#2FA084] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#2FA084]/20 hover:opacity-95">
                        <x-icon name="o-magnifying-glass" class="w-4 h-4" />
                        Browse Products
                    </a>
                </div>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($products as $product)
                    @php
                        $mainImg = $product->image ? Storage::url($product->image) : null;
                        $isDirect = $product->isDirectSell();
                        $price = $isDirect ? $product->sale_price : ($product->auction?->current_price ?: $product->starting_bid);
                    @endphp
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs transition-all hover:shadow-md dark:border-gray-800 dark:bg-[#181A1F]">
                        <div>
                            {{-- Image Container --}}
                            <div class="relative aspect-4/3 overflow-hidden bg-gray-100 dark:bg-gray-800">
                                @if($mainImg)
                                    <img src="{{ $mainImg }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center text-gray-400">
                                        <x-icon name="o-photo" class="h-12 w-12" />
                                    </div>
                                @endif

                                {{-- Listing Type Badge --}}
                                <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                                    @if($isDirect)
                                        <span class="rounded-lg bg-[#1F6F5F] px-2.5 py-1 text-[11px] font-extrabold text-white shadow-sm">
                                            Direct Sell
                                        </span>
                                    @else
                                        <span class="rounded-lg bg-purple-600 px-2.5 py-1 text-[11px] font-extrabold text-white shadow-sm">
                                            Auction
                                        </span>
                                    @endif

                                    @if($product->condition)
                                        <span class="rounded-lg bg-black/70 backdrop-blur-xs px-2 py-1 text-[10px] font-bold text-white">
                                            {{ str($product->condition)->replace('-', ' ')->title() }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Remove from Wishlist Button --}}
                                <button type="button"
                                        wire:click="removeFromWishlist({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        class="absolute top-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-rose-500 shadow-md backdrop-blur-xs hover:bg-rose-500 hover:text-white transition-colors"
                                        title="Remove from Wishlist">
                                    <x-icon name="s-heart" class="h-5 w-5" />
                                </button>
                            </div>

                            {{-- Product Content --}}
                            <div class="p-4">
                                <p class="text-xs font-semibold text-[#1F6F5F] dark:text-[#7CE0C5]">
                                    {{ $product->category?->name ?? 'General' }}
                                </p>
                                <h3 class="mt-1 font-bold text-gray-900 line-clamp-1 dark:text-white">
                                    <a href="{{ route('user.products.show', $product->slug) }}" wire:navigate class="hover:underline">
                                        {{ $product->name }}
                                    </a>
                                </h3>

                                <div class="mt-3 flex items-baseline justify-between">
                                    <p class="text-lg font-black text-gray-900 dark:text-white">
                                        Rs {{ number_format((float) $price) }}
                                    </p>
                                    @if($product->retail_price && $product->retail_price > $price)
                                        <p class="text-xs font-semibold text-gray-400 line-through">
                                            Rs {{ number_format((float) $product->retail_price) }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Meetup Location Tag --}}
                                @if($isDirect && ($product->meetup_location || $product->location))
                                    <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                        <x-icon name="o-map-pin" class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                        <span class="truncate font-medium">Meetup: {{ $product->meetup_location ?: $product->location }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Action Footer --}}
                        <div class="border-t border-gray-100 p-3 dark:border-gray-800">
                            @if($isDirect)
                                <button type="button"
                                        wire:click="addToCart({{ $product->id }})"
                                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-linear-to-r from-[#1F6F5F] to-[#2FA084] px-4 py-2 text-xs font-bold text-white hover:opacity-90">
                                    <x-icon name="o-shopping-cart" class="w-4 h-4" />
                                    Add to Cart
                                </button>
                            @else
                                <a href="{{ route('user.auction.detail', $product->auction->id) }}" wire:navigate
                                   class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                                    <x-icon name="o-ticket" class="w-4 h-4" />
                                    Play Live Auction
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
