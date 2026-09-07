@php
    $conditionLabel = fn ($condition) => str($condition)->replace('-', ' ')->title();
    $priceFor = function ($product) {
        if ($product->listing_type->value === 'auction' && $product->auction) {
            return $product->auction->current_price ?: $product->starting_bid;
        }

        return $product->sale_price;
    };
    $targetUrl = fn ($product) => route('user.products.show', $product->slug);
    $postRoute = Auth::check() ? route('user.products.create') : route('user.login');
@endphp

<div class="marketplace-ui min-h-screen bg-[#F7F8FA] text-gray-950 dark:bg-gray-900 dark:text-gray-100">
    @livewire('components.user.search-filter-component')

    <section class="mx-auto grid max-w-370 grid-cols-1 gap-4 px-3 pb-16 pt-4 lg:grid-cols-[280px_minmax(0,1fr)_230px] lg:px-4">
        <x-user.category-sidebar :categories="$categories" :post-route="$postRoute" />

        <main class="min-w-0 space-y-4">
            <div class="flex gap-2 overflow-x-auto pb-1 lg:hidden">
                @foreach($categories->take(8) as $category)
                    <div x-data="{ open: false }" class="shrink-0">
                        @if($category->children->isNotEmpty())
                            <button type="button" @click="open = ! open" class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 font-bold dark:border-gray-800 dark:bg-[#181A1F]">
                                {{ $category->name }}
                                <x-icon name="o-chevron-down" class="ui-icon transition-transform" ::class="open ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="open" @click.outside="open = false" class="absolute z-30 mt-2 min-w-44 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-[#181A1F]" style="display: none;">
                                @foreach($category->children as $childCategory)
                                    <a href="{{ route('user.search.product', ['category' => $childCategory->id]) }}" wire:navigate class="block px-3 py-2 font-semibold text-gray-600 hover:bg-gray-50 hover:text-[#0C8FE8] dark:text-gray-300 dark:hover:bg-gray-800">{{ $childCategory->name }}</a>
                                @endforeach
                            </div>
                        @else
                            <button type="button" class="block rounded-md border border-gray-200 bg-white px-3 py-2 font-bold text-gray-500 dark:border-gray-800 dark:bg-[#181A1F] dark:text-gray-400">{{ $category->name }}</button>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ Auth::check() ? route('user.wishlist') : route('user.login') }}" wire:navigate class="hidden rounded-md border border-gray-200 bg-white px-3 py-2 font-bold text-gray-700 hover:border-rose-400 hover:text-rose-600 dark:border-gray-800 dark:bg-[#181A1F] dark:text-gray-200 sm:inline-flex gap-1.5 items-center">
                    <x-icon name="o-heart" class="w-4 h-4 text-rose-500" />
                    Wishlist
                </a>
                @guest
                    <a href="{{ route('user.login') }}" wire:navigate class="rounded-md border border-gray-200 bg-white px-3 py-2 font-bold text-gray-700 hover:border-sky-400 hover:text-sky-600 dark:border-gray-800 dark:bg-[#181A1F] dark:text-gray-200">Sign in / Sign up</a>
                @endguest
                <a href="{{ $postRoute }}" wire:navigate class="inline-flex items-center gap-2 rounded-md bg-[#0C8FE8] px-3 py-2 font-black text-white hover:bg-[#0877C2]">
                    <x-icon name="o-plus" class="ui-icon" />
                    Post for free
                </a>
            </div>

            <section
                x-data="{
                    current: 0,
                    total: {{ count($bannerSlides) }},
                    timer: null,
                    start() {
                        this.timer = setInterval(() => this.next(), 4500);
                    },
                    stop() {
                        clearInterval(this.timer);
                    },
                    next() {
                        this.current = (this.current + 1) % this.total;
                    },
                    previous() {
                        this.current = (this.current + this.total - 1) % this.total;
                    },
                }"
                x-init="start()"
                @mouseenter="stop()"
                @mouseleave="start()"
                class="overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200 dark:bg-[#181A1F] dark:ring-gray-800"
            >
                <div class="relative aspect-16/7 min-h-52.5 overflow-hidden sm:aspect-16/5">
                    @foreach($bannerSlides as $index => $slide)
                        <div
                            x-show="current === {{ $index }}"
                            x-transition.opacity.duration.300ms
                            class="absolute inset-0 text-white"
                            style="background: {{ $slide['gradient'] }}; {{ $index === 0 ? '' : 'display: none;' }}"
                        >
                            <div class="grid h-full grid-cols-1 items-center gap-4 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_260px]">
                                <div class="max-w-xl">
                                    <p class="ui-small font-black uppercase tracking-widest text-white/80">{{ $slide['eyebrow'] }}</p>
                                    <h1 class="mt-2 max-w-lg text-[22px] font-black leading-tight sm:text-[30px]">{{ $slide['title'] }}</h1>
                                    <p class="mt-3 max-w-lg text-[12px] font-semibold leading-relaxed text-white/90 sm:text-[14px]">{{ $slide['copy'] }}</p>
                                    <a
                                        href="{{ $slide['target'] === 'post' ? $postRoute : $slide['target'] }}"
                                        @if($slide['target'] === 'post') wire:navigate @endif
                                        class="mt-5 inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 font-black text-gray-950 shadow-sm"
                                    >
                                        {{ $slide['cta'] }}
                                        <x-icon name="o-arrow-right" class="ui-icon" />
                                    </a>
                                </div>
                                <div class="hidden h-44 items-center justify-center rounded-md bg-white/15 ring-1 ring-white/25 lg:flex">
                                    <x-icon name="{{ $slide['icon'] }}" class="h-20 w-20 text-white" />
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <button type="button" @click="previous()" class="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-md bg-black/45 text-white hover:bg-black/70">
                        <x-icon name="o-chevron-left" class="ui-icon-lg" />
                    </button>
                    <button type="button" @click="next()" class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-md bg-black/45 text-white hover:bg-black/70">
                        <x-icon name="o-chevron-right" class="ui-icon-lg" />
                    </button>

                    <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5">
                        @foreach($bannerSlides as $index => $slide)
                            <button type="button" @click="current = {{ $index }}" class="h-1.5 rounded-full bg-white transition-all" :class="current === {{ $index }} ? 'w-7 opacity-100' : 'w-2 opacity-50'" aria-label="Go to banner {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="trending" class="rounded-md border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-[#181A1F]">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="ui-heading flex items-center gap-2 font-black">
                        <x-icon name="o-arrow-trending-up" class="ui-icon-lg text-[#0C8FE8]" />
                        Trending
                    </h2>
                    <div class="flex gap-1.5">
                        <button type="button" onclick="document.getElementById('trending-carousel').scrollBy({left: -520, behavior: 'smooth'})" class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 hover:border-[#0C8FE8] hover:text-[#0C8FE8] dark:border-gray-700">
                            <x-icon name="o-chevron-left" class="ui-icon" />
                        </button>
                        <button type="button" onclick="document.getElementById('trending-carousel').scrollBy({left: 520, behavior: 'smooth'})" class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 hover:border-[#0C8FE8] hover:text-[#0C8FE8] dark:border-gray-700">
                            <x-icon name="o-chevron-right" class="ui-icon" />
                        </button>
                    </div>
                </div>

                <div id="trending-carousel" class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-1">
                    @forelse($trendingProducts as $product)
                        <div class="group relative min-w-40 snap-start overflow-hidden rounded-md border border-gray-200 bg-white transition hover:border-[#0C8FE8] dark:border-gray-800 dark:bg-gray-900 sm:min-w-47.5 lg:min-w-45">
                            <a href="{{ $targetUrl($product) }}" wire:navigate class="block">
                                <div class="aspect-4/3 relative bg-gray-100 dark:bg-gray-800">
                                    @if($product->image)
                                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition group-hover:scale-105">
                                    @else
                                        <div class="flex h-full items-center justify-center"><x-icon name="o-photo" class="h-9 w-9 text-gray-300" /></div>
                                    @endif
                                    <div class="absolute top-2 left-2">
                                        @if($product->isAuction())
                                            <span x-data="auctionCountdown('{{ $product->auction?->start_time?->toIso8601String() }}', '{{ $product->auction?->end_time?->toIso8601String() }}')" class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2 py-0.5 text-[9px] font-black text-white shadow-sm">
                                                <span x-text="label"></span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded bg-[#0C8FE8] px-2 py-0.5 text-[9px] font-black text-white shadow-sm">
                                                DIRECT SELL
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="p-2.5 pr-9">
                                    <h3 class="line-clamp-2 min-h-9 font-black leading-snug text-gray-900 dark:text-gray-100">{{ $product->name }}</h3>
                                    <p class="ui-price mt-1 font-black text-[#0C8FE8]">
                                        {{ $product->isAuction() ? 'Current Bid: ' : '' }}Rs {{ number_format((float) $priceFor($product)) }}
                                    </p>
                                    <span class="mt-2 inline-flex rounded-md bg-gray-100 px-2 py-1 ui-small font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $conditionLabel($product->condition) }}</span>
                                </div>
                            </a>
                            <button
                                type="button"
                                wire:click="toggleBookmark({{ $product->id }})"
                                wire:loading.attr="disabled"
                                wire:target="toggleBookmark({{ $product->id }})"
                                class="absolute right-2 top-2 z-10 flex h-7 w-7 items-center justify-center rounded-md bg-white/90 shadow-sm hover:bg-white disabled:opacity-60 dark:bg-gray-900/90"
                                aria-label="Toggle bookmark for {{ $product->name }}"
                            >
                                <x-icon
                                    name="{{ in_array($product->id, $bookmarkedProductIds, true) ? 's-bookmark' : 'o-bookmark' }}"
                                    @class(['w-4 h-4', 'text-[#0C8FE8]' => in_array($product->id, $bookmarkedProductIds, true), 'text-gray-600 dark:text-gray-300' => ! in_array($product->id, $bookmarkedProductIds, true)])
                                />
                            </button>
                            @if($product->isDirectSell() && (! Auth::check() || (int) $product->seller_id !== (int) Auth::id()))
                                <button
                                    type="button"
                                    wire:click="addToCart({{ $product->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="addToCart({{ $product->id }})"
                                    class="absolute bottom-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded-md bg-white/90 shadow-sm hover:bg-white disabled:opacity-60 dark:bg-gray-900/90"
                                    aria-label="Add {{ $product->name }} to cart"
                                >
                                    <x-icon name="o-shopping-cart" class="w-4 h-4 text-gray-700 dark:text-gray-200" />
                                </button>
                            @endif
                        </div>
                    @empty
                        <div class="w-full rounded-md border border-dashed border-gray-300 p-8 text-center font-bold text-gray-500 dark:border-gray-700">Trending products will appear here.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-md border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-[#181A1F]">
                <div class="flex flex-wrap gap-2">
                    @foreach($popularSearches as $search)
                        <a href="{{ route('user.search.product', ['search' => $search]) }}" wire:navigate class="rounded-md bg-gray-100 px-3 py-2 font-bold text-gray-700 hover:bg-sky-50 hover:text-[#0C8FE8] dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-sky-950/40">{{ $search }}</a>
                    @endforeach
                </div>
            </section>

            @if($featuredProducts->isNotEmpty())
                <section id="featured" class="rounded-md border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-[#181A1F]">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="ui-heading flex items-center gap-2 font-black">
                            <x-icon name="o-hand-thumb-up" class="ui-icon-lg text-[#0C8FE8]" />
                            Recommended
                        </h2>
                        <span class="ui-small font-black uppercase tracking-widest text-gray-500">Featured</span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach($featuredProducts->take(4) as $product)
                            <div class="relative rounded-md border border-gray-100 p-2 transition hover:border-[#0C8FE8] dark:border-gray-800">
                                <a href="{{ $targetUrl($product) }}" wire:navigate class="grid grid-cols-[112px_minmax(0,1fr)] gap-3">
                                    <div class="aspect-square relative overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800">
                                        @if($product->image)
                                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full items-center justify-center"><x-icon name="o-photo" class="h-9 w-9 text-gray-300" /></div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 pr-7">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            @if($product->isAuction())
                                                <span x-data="auctionCountdown('{{ $product->auction?->start_time?->toIso8601String() }}', '{{ $product->auction?->end_time?->toIso8601String() }}')" class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2 py-0.5 text-[9px] font-black text-white"><span x-text="label"></span></span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded bg-[#0C8FE8] px-2 py-0.5 text-[9px] font-black text-white">DIRECT SELL</span>
                                            @endif
                                        </div>
                                        <h3 class="line-clamp-2 font-black text-gray-900 dark:text-gray-100">{{ $product->name }}</h3>
                                        <p class="mt-1 line-clamp-2 font-semibold text-gray-500 text-xs">{{ $product->description }}</p>
                                        <p class="ui-price mt-2 font-black text-[#0C8FE8]">Rs {{ number_format((float) $priceFor($product)) }}</p>
                                        <p class="mt-1 truncate font-semibold text-gray-500 text-xs">{{ $product->location ?: $product->category?->name ?: 'Sajha Auction' }}</p>
                                    </div>
                                </a>
                                <button
                                    type="button"
                                    wire:click="toggleBookmark({{ $product->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleBookmark({{ $product->id }})"
                                    class="absolute right-2 top-2 z-10 flex h-7 w-7 items-center justify-center rounded-md bg-white/90 shadow-sm hover:bg-white disabled:opacity-60 dark:bg-gray-900/90"
                                    aria-label="Toggle bookmark for {{ $product->name }}"
                                >
                                    <x-icon
                                        name="{{ in_array($product->id, $bookmarkedProductIds, true) ? 's-bookmark' : 'o-bookmark' }}"
                                        @class(['w-4 h-4', 'text-[#0C8FE8]' => in_array($product->id, $bookmarkedProductIds, true), 'text-gray-600 dark:text-gray-300' => ! in_array($product->id, $bookmarkedProductIds, true)])
                                    />
                                </button>
                                @if($product->isDirectSell() && (! Auth::check() || (int) $product->seller_id !== (int) Auth::id()))
                                    <button
                                        type="button"
                                        wire:click="addToCart({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="addToCart({{ $product->id }})"
                                        class="absolute bottom-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded-md bg-white/90 shadow-sm hover:bg-white disabled:opacity-60 dark:bg-gray-900/90"
                                        aria-label="Add {{ $product->name }} to cart"
                                    >
                                        <x-icon name="o-shopping-cart" class="w-4 h-4 text-gray-700 dark:text-gray-200" />
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section id="latest" class="rounded-md border border-gray-200 bg-white dark:border-gray-800 dark:bg-[#181A1F]">
                <div class="sticky top-15 z-20 flex items-center justify-between border-b border-gray-200 bg-white px-3 py-3 dark:border-gray-800 dark:bg-[#181A1F]">
                    <div class="flex gap-5">
                        <a href="#latest" class="flex items-center gap-2 border-b-2 border-[#0C8FE8] pb-2 font-black text-[#0C8FE8]">
                            <x-icon name="o-arrow-up-tray" class="ui-icon" />
                            Latest Uploads
                        </a>
                        <a href="#featured" class="flex items-center gap-2 pb-2 font-black text-gray-600 dark:text-gray-300">
                            <x-icon name="o-hand-thumb-up" class="ui-icon" />
                            Recommended
                        </a>
                    </div>
                    <x-icon name="o-squares-2x2" class="ui-icon-lg text-gray-500" />
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($latestProducts as $product)
                        <article class="relative transition hover:bg-gray-50 dark:hover:bg-[#202228]">
                            <a href="{{ $targetUrl($product) }}" wire:navigate class="grid grid-cols-[104px_minmax(0,1fr)] gap-3 p-3 sm:grid-cols-[150px_minmax(0,1fr)]">
                                <div class="aspect-square overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800 relative">
                                    @if($product->image)
                                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full items-center justify-center"><x-icon name="o-photo" class="h-10 w-10 text-gray-300" /></div>
                                    @endif
                                </div>

                                <div class="min-w-0 pr-8">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                @if($product->isAuction())
                                                    <span x-data="auctionCountdown('{{ $product->auction?->start_time?->toIso8601String() }}', '{{ $product->auction?->end_time?->toIso8601String() }}')" class="rounded-md bg-emerald-600 px-2 py-0.5 text-[10px] font-black text-white flex items-center gap-1">
                                                        <span x-show="isLive" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                                        <span x-text="label"></span>
                                                    </span>
                                                @else
                                                    <span class="rounded-md bg-sky-600 px-2 py-0.5 text-[10px] font-black text-white">
                                                        🏷️ DIRECT SELL
                                                    </span>
                                                @endif
                                                <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $conditionLabel($product->condition) }}</span>
                                            </div>
                                            <h2 class="ui-heading line-clamp-2 font-black text-gray-900 dark:text-gray-100 mt-0.5">{{ $product->name }}</h2>
                                        </div>
                                        <span class="hidden shrink-0 font-semibold text-gray-500 md:inline text-xs">{{ $product->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-1.5 line-clamp-2 font-semibold text-gray-500 text-xs sm:text-sm">{{ $product->description }}</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="ui-price font-black text-[#0C8FE8]">
                                            {{ $product->isAuction() ? 'Current Bid: ' : '' }}Rs {{ number_format((float) $priceFor($product)) }}
                                        </span>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-3 font-semibold text-gray-500 text-xs">
                                        <span class="truncate">{{ $product->location ?: $product->category?->name ?: 'Sajha Auction' }}</span>
                                        <span class="md:hidden">{{ $product->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-1 truncate font-semibold text-gray-500 text-xs">{{ $product->user?->name ?? 'Seller' }}</p>
                                </div>
                            </a>
                            <div x-data="{ open: false, copied: false }" class="absolute right-3 top-4">
                                <button type="button" @click="open = ! open" class="rounded-md p-1 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="More options for {{ $product->name }}">
                                    <x-icon name="o-ellipsis-vertical" class="ui-icon-lg text-gray-700 dark:text-gray-300" />
                                </button>
                                <div x-show="open" @click.outside="open = false; copied = false" x-cloak class="absolute right-0 z-30 mt-1 w-40 overflow-hidden rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-800 dark:bg-[#181A1F]" style="display: none;">
                                    <a href="{{ $targetUrl($product) }}" wire:navigate class="flex items-center gap-2 px-3 py-2 font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <x-icon name="o-eye" class="ui-icon" />
                                        View details
                                    </a>
                                    <button
                                        type="button"
                                        @click="navigator.clipboard.writeText('{{ $targetUrl($product) }}'); copied = true; setTimeout(() => { open = false; copied = false }, 900)"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-left font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800"
                                    >
                                        <x-icon name="o-link" class="ui-icon" />
                                        <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="absolute bottom-4 right-3 flex items-center gap-1">
                                @if($product->isDirectSell() && (! Auth::check() || (int) $product->seller_id !== (int) Auth::id()))
                                    <button
                                        type="button"
                                        wire:click="addToCart({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="addToCart({{ $product->id }})"
                                        class="rounded-md p-1 hover:bg-gray-100 disabled:opacity-60 dark:hover:bg-gray-800"
                                        aria-label="Add {{ $product->name }} to cart"
                                    >
                                        <x-icon name="o-shopping-cart" class="ui-icon-lg cursor-pointer text-gray-800 dark:text-gray-100" />
                                    </button>
                                @endif
                                <button
                                    type="button"
                                    wire:click="toggleBookmark({{ $product->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleBookmark({{ $product->id }})"
                                    class="rounded-md p-1 hover:bg-gray-100 disabled:opacity-60 dark:hover:bg-gray-800"
                                    aria-label="Toggle bookmark for {{ $product->name }}"
                                >
                                    <x-icon
                                        name="{{ in_array($product->id, $bookmarkedProductIds, true) ? 's-bookmark' : 'o-bookmark' }}"
                                        @class([
                                            'ui-icon-lg cursor-pointer',
                                            'text-[#0C8FE8]' => in_array($product->id, $bookmarkedProductIds, true),
                                            'text-gray-800 dark:text-gray-100' => ! in_array($product->id, $bookmarkedProductIds, true),
                                        ])
                                    />
                                </button>
                            </div>
                        </article>
                    @empty
                        <div class="p-10 text-center">
                            <x-icon name="o-inbox-stack" class="mx-auto h-8 w-8 text-gray-300" />
                            <p class="mt-4 text-sm font-bold text-gray-500">No approved products are available.</p>
                        </div>
                    @endforelse
                </div>

                <div class="p-3">{{ $latestProducts->links() }}</div>
            </section>
        </main>

        <aside class="hidden space-y-4 lg:block">
            <div id="buyer-safety" class="rounded-md border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-[#181A1F]">
                <h2 class="ui-heading font-black">Marketplace tools</h2>
                <div class="mt-3 space-y-2">
                    <a href="{{ $postRoute }}" wire:navigate class="flex items-center justify-between rounded-md bg-[#0C8FE8] px-3 py-2 font-black text-white">
                        Post for free
                        <x-icon name="o-arrow-right" class="ui-icon" />
                    </a>
                    <a href="#featured" class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2 font-bold hover:border-[#0C8FE8] hover:text-[#0C8FE8] dark:border-gray-800">
                        Recommended items
                        <x-icon name="o-chevron-right" class="ui-icon" />
                    </a>
                    <a href="#latest" class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2 font-bold hover:border-[#0C8FE8] hover:text-[#0C8FE8] dark:border-gray-800">
                        Latest uploads
                        <x-icon name="o-chevron-right" class="ui-icon" />
                    </a>
                </div>
            </div>

            <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-[#181A1F]">
                <h2 class="ui-heading font-black">Buyer safety</h2>
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-3 font-semibold text-gray-600 dark:text-gray-300">
                    <a href="{{ route('info.page', 'safety-tips') }}" wire:navigate class="underline">Safety Tips</a>
                    <a href="{{ route('info.page', 'posting-rules') }}" wire:navigate class="underline">Posting Rules</a>
                    <a href="{{ route('faqs') }}" wire:navigate class="underline">FAQ</a>
                    <a href="{{ asset('assets/documents/terms-and-conditions.pdf') }}" target="_blank" rel="noopener" class="underline">Terms of Use</a>
                    <a href="{{ asset('assets/documents/terms-and-conditions.pdf') }}" target="_blank" rel="noopener" class="underline">Privacy Policy</a>
                    <a href="mailto:{{ config('mail.from.address') }}" class="underline">Contact Us</a>
                    <a href="mailto:{{ config('mail.from.address') }}?subject=Bug%20Report" class="underline">Report bugs</a>
                </div>
            </div>
        </aside>
    </section>
    <script>
        function auctionCountdown(startTime, endTime) {
            return {
                startTime: new Date(startTime).getTime(),
                endTime: new Date(endTime).getTime(),
                label: '',
                isLive: false,
                timer: null,
                init() {
                    this.update();
                    this.timer = setInterval(() => this.update(), 1000);
                },
                update() {
                    const now = Date.now();
                    this.isLive = now >= this.startTime && now <= this.endTime;

                    if (now > this.endTime) {
                        this.label = 'AUCTION ENDED';
                        clearInterval(this.timer);
                        return;
                    }

                    if (now < this.startTime) {
                        const remaining = this.startTime - now;
                        const hours = Math.floor(remaining / 3600000);
                        const minutes = Math.floor((remaining % 3600000) / 60000);
                        const seconds = Math.floor((remaining % 60000) / 1000);
                        this.label = `STARTS IN ${hours}h ${minutes}m ${seconds}s`;
                        return;
                    }

                    this.label = 'LIVE AUCTION';
                },
            };
        }
    </script>
</div>
