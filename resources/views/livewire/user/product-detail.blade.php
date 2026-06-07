@php
    $mainImage = $product->image ? Storage::url($product->image) : null;
    $price = $product->listing_type->value === 'auction' && $product->auction
        ? ($product->auction->current_price ?: $product->starting_bid)
        : $product->sale_price;
    $condition = str($product->condition)->replace('-', ' ')->title();
    $specLines = collect(preg_split('/\r\n|\r|\n/', (string) $product->specifications))->filter();
@endphp

<div class="marketplace-ui min-h-screen bg-white text-gray-950 dark:bg-[#101114] dark:text-gray-100">
    <div class="mx-auto max-w-[1280px] px-2 pb-14 pt-3 sm:px-3 lg:grid lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-5 lg:px-4 lg:pt-5">
        <main>
            <div class="mb-3 flex items-center gap-2 lg:hidden">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                    <x-icon name="o-sparkles" class="ui-icon" />
                </div>
                <div class="flex h-9 min-w-0 flex-1 items-center gap-2 rounded-lg bg-[#F1F1F1] px-3">
                    <input type="text" placeholder="Search for anything" class="min-w-0 flex-1 bg-transparent text-[11px] outline-none placeholder:text-gray-500">
                    <x-icon name="o-magnifying-glass" class="ui-icon text-gray-500" />
                </div>
                <button type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-sky-400 text-sky-500">
                    <x-icon name="o-rocket-launch" class="ui-icon" />
                </button>
            </div>

            <section class="lg:grid lg:grid-cols-[minmax(0,640px)_minmax(280px,1fr)] lg:gap-5">
                <div>
                    <div x-data="{ selectedImage: '{{ $mainImage }}' }" class="space-y-3">
                        <div class="aspect-[4/3] overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800 lg:aspect-[16/9]">
                            @if($mainImage)
                                <img :src="selectedImage" alt="{{ $product->name }}" class="h-full w-full object-cover lg:object-contain">
                            @else
                                <div class="flex h-full items-center justify-center"><x-icon name="o-photo" class="h-16 w-16 text-gray-300" /></div>
                            @endif
                        </div>
                        @if($product->images->count() > 1)
                            <div class="hidden gap-2 lg:flex">
                                @foreach($product->images as $image)
                                    <button type="button" @click="selectedImage = '{{ Storage::url($image->path) }}'" class="h-14 w-14 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                                        <img src="{{ Storage::url($image->path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mt-3 flex items-center justify-between text-gray-500">
                        <span class="flex items-center gap-1"><x-icon name="o-eye" class="ui-icon" /> {{ number_format($product->views_count) }} views</span>
                        <span class="rounded-md bg-gray-100 px-2 py-0.5 font-semibold dark:bg-gray-800">{{ $condition }}</span>
                    </div>

                    <div class="mt-6 lg:hidden">
                        <p class="font-semibold text-gray-500">{{ $product->user?->name ?? 'Seller' }}</p>
                        <p class="ui-small text-gray-500">{{ $product->user?->phone }}</p>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <button class="rounded-lg bg-gray-100 px-3 py-2 font-bold dark:bg-gray-800">Save for later</button>
                            <button class="rounded-lg bg-gray-100 px-3 py-2 font-bold dark:bg-gray-800">Start a chat</button>
                        </div>
                    </div>

                    <div class="mt-5 rounded-lg bg-gray-50 p-3 leading-5 text-gray-600 dark:bg-[#181A1F] dark:text-gray-300">
                        Note: We recommend you physically inspect the product before making payment. Avoid paying fees or advance payment to sellers.
                    </div>

                    <section class="mt-5">
                        <h1 class="ui-heading font-bold leading-tight">{{ $product->name }}</h1>
                        <p class="ui-price mt-3 font-bold">Rs {{ number_format((float) $price) }}</p>

                        <div class="mt-5 border-b border-gray-200 dark:border-gray-800">
                            <div class="flex items-center gap-5 font-bold">
                                <span class="border-b-2 border-gray-950 pb-2 dark:border-white">Description</span>
                                <span class="pb-2">Comments</span>
                                <span class="pb-2">Location</span>
                            </div>
                        </div>

                        <div class="mt-3 max-h-44 overflow-hidden font-semibold leading-5 text-gray-800 dark:text-gray-200 lg:max-h-none">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </section>

                    <section class="mt-6">
                        <h2 class="ui-heading mb-2 font-bold">General</h2>
                        <div class="overflow-hidden rounded-lg bg-gray-100 dark:bg-[#181A1F]">
                            <div class="grid grid-cols-[160px_1fr] border-b border-white/80 dark:border-gray-800">
                                <div class="p-3 font-bold">AD ID</div>
                                <div class="p-3">{{ $product->sku }}</div>
                            </div>
                            <div class="grid grid-cols-[160px_1fr] border-b border-white/80 dark:border-gray-800">
                                <div class="p-3 font-bold">Location</div>
                                <div class="p-3">{{ $product->location ?: 'Not provided' }}</div>
                            </div>
                            <div class="grid grid-cols-[160px_1fr] border-b border-white/80 dark:border-gray-800">
                                <div class="p-3 font-bold">Delivery</div>
                                <div class="p-3">{{ $product->delivery_available ? 'Available' : 'Not Available' }}</div>
                            </div>
                            <div class="grid grid-cols-[160px_1fr] border-b border-white/80 dark:border-gray-800">
                                <div class="p-3 font-bold">Ads Posted</div>
                                <div class="p-3">{{ $product->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="grid grid-cols-[160px_1fr]">
                                <div class="p-3 font-bold">Ads Expiry</div>
                                <div class="p-3">{{ $product->expires_at?->toDateString() ?? 'Not set' }}</div>
                            </div>
                        </div>
                    </section>

                    @if($specLines->isNotEmpty())
                        <section class="mt-6">
                            <h2 class="ui-heading mb-2 font-bold">Specifications</h2>
                            <div class="overflow-hidden rounded-lg bg-gray-100 dark:bg-[#181A1F]">
                                @foreach($specLines as $line)
                                    <div class="grid grid-cols-[160px_1fr] border-b border-white/80 last:border-b-0 dark:border-gray-800">
                                        <div class="p-3 font-bold">{{ str($line)->before(':')->limit(24) }}</div>
                                        <div class="p-3">{{ str($line)->contains(':') ? str($line)->after(':') : $line }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                <aside class="mt-6 hidden lg:block">
                    <div class="sticky top-24 space-y-5">
                        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-[#181A1F]">
                            <h2 class="ui-heading font-bold">{{ $product->name }}</h2>
                            <p class="ui-price mt-3 font-black">Rs {{ number_format((float) $price) }}</p>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="rounded-lg bg-gray-100 p-2.5 dark:bg-gray-800">
                                    <p class="font-bold">Type</p>
                                    <p>{{ $product->listing_type->label() }}</p>
                                </div>
                                <div class="rounded-lg bg-gray-100 p-2.5 dark:bg-gray-800">
                                    <p class="font-bold">Views</p>
                                    <p>{{ number_format($product->views_count) }}</p>
                                </div>
                            </div>
                            <div class="mt-4 flex gap-2">
                                <button class="flex-1 rounded-lg bg-gray-100 px-3 py-2 font-bold dark:bg-gray-800">Save</button>
                                <button class="flex-1 rounded-lg bg-[#1F6F5F] px-3 py-2 font-bold text-white">Chat</button>
                            </div>
                        </div>

                        @if($product->auction)
                            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-[#181A1F]">
                                <h3 class="ui-heading font-bold">Auction</h3>
                                <div class="mt-4 space-y-3">
                                    <div class="flex justify-between"><span>Total bids</span><strong>{{ $product->auction->total_bids }}</strong></div>
                                    <div class="flex justify-between"><span>Current bid</span><strong>Rs {{ number_format((float) $product->auction->current_price) }}</strong></div>
                                    <div class="flex justify-between"><span>Ends</span><strong>{{ $product->auction->end_time?->format('Y-m-d H:i') }}</strong></div>
                                </div>
                                <a href="{{ route('user.auction.detail', $product->auction) }}" wire:navigate class="mt-4 block rounded-lg bg-[#1F6F5F] px-3 py-2 text-center font-bold text-white">Place Bid</a>
                            </div>
                        @endif
                    </div>
                </aside>
            </section>

            @if($similarProducts->isNotEmpty())
                <section class="mt-8">
                    <h2 class="ui-heading mb-3 font-bold">Similar Products</h2>
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        @foreach($similarProducts as $similarProduct)
                            <a href="{{ route('user.products.show', $similarProduct->slug) }}" wire:navigate class="block">
                                <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                                    @if($similarProduct->image)
                                        <img src="{{ Storage::url($similarProduct->image) }}" alt="{{ $similarProduct->name }}" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <h3 class="mt-2 line-clamp-2 font-bold">{{ $similarProduct->name }}</h3>
                                <p class="mt-1 font-bold">Rs {{ number_format((float) ($similarProduct->sale_price ?? $similarProduct->starting_bid)) }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>

        <aside class="hidden lg:block">
            <div class="sticky top-24 overflow-hidden bg-[#06241C] p-8 text-white">
                <h2 class="ui-heading font-bold">Join our Newsletter</h2>
                <p class="mt-5 text-emerald-100">Stay updated with latest auctions and exclusive offers.</p>
                <div class="mt-6 flex rounded-lg border border-white/10 bg-white/10 p-1.5">
                    <input type="email" placeholder="your@email.com" class="min-w-0 flex-1 bg-transparent px-3 outline-none placeholder:text-emerald-100">
                    <button class="rounded-lg bg-[#2FA084] px-4 py-2 font-bold">Join</button>
                </div>
                <div class="mt-24 border-t border-white/10 pt-6 text-xs font-bold uppercase tracking-widest text-emerald-100">System online</div>
            </div>
        </aside>
    </div>
</div>
