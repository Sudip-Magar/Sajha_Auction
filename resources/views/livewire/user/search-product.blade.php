@php
    $conditionLabel = fn ($condition) => str($condition)->replace('-', ' ')->title();
    $priceFor = function ($product) {
        if ($product->listing_type->value === 'auction' && $product->auction) {
            return $product->auction->current_price ?: $product->starting_bid;
        }

        return $product->sale_price;
    };
    $targetUrl = fn ($product) => route('user.products.show', $product->slug);
@endphp

<div class="marketplace-ui min-h-screen bg-[#F7F8FA] text-gray-950 dark:bg-gray-900 dark:text-gray-100">
    <section class="mx-auto grid max-w-[1480px] grid-cols-1 gap-4 px-3 pb-16 pt-4 lg:grid-cols-[280px_minmax(0,1fr)_230px] lg:px-4">
        <x-user.category-sidebar :categories="$categories" :active-category="$category" />

        <main class="min-w-0 space-y-4">
            <x-header title="Search Products" subtitle="Find approved products by name, category, condition, and price" separator progress-indicator />

            <section class="rounded-md border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-[#181A1F]">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_200px_170px_auto]">
                    <label class="flex h-11 min-w-0 items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-4 focus-within:border-[#0C8FE8] dark:border-gray-800 dark:bg-[#202228]">
                        <x-icon name="o-magnifying-glass" class="ui-icon-lg text-gray-500" />
                        <input
                            type="search"
                            wire:model.live.debounce.350ms="search"
                            placeholder="Search product, category, or condition"
                            class="min-w-0 flex-1 bg-transparent font-semibold outline-none placeholder:text-gray-400"
                        >
                    </label>

                    <label class="flex h-11 items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-4 dark:border-gray-800 dark:bg-[#202228]">
                        <x-icon name="o-squares-2x2" class="ui-icon text-gray-500" />
                        <select wire:model.live="category" class="min-w-0 flex-1 bg-transparent font-semibold outline-none">
                            <option value="">All categories</option>
                            @foreach($categories as $categoryOption)
                                <option value="{{ $categoryOption->id }}">{{ $categoryOption->name }}</option>
                                @foreach($categoryOption->children as $childCategory)
                                    <option value="{{ $childCategory->id }}">- {{ $childCategory->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </label>

                    <label class="flex h-11 items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-4 dark:border-gray-800 dark:bg-[#202228]">
                        <x-icon name="o-sparkles" class="ui-icon text-gray-500" />
                        <select wire:model.live="condition" class="min-w-0 flex-1 bg-transparent font-semibold outline-none">
                            <option value="">Any condition</option>
                            @foreach($conditions as $conditionValue => $conditionName)
                                <option value="{{ $conditionValue }}">{{ $conditionName }}</option>
                            @endforeach
                        </select>
                    </label>

                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="flex h-11 items-center justify-center gap-2 rounded-md border border-gray-200 px-4 font-black hover:border-[#0C8FE8] hover:text-[#0C8FE8] dark:border-gray-800"
                    >
                        <x-icon name="o-x-mark" class="ui-icon" />
                        Clear
                    </button>
                </div>
            </section>

            <div class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-800 dark:bg-[#181A1F]">
                <div class="border-b border-gray-100 px-4 py-3 font-bold text-gray-500 dark:border-gray-800">
                    {{ $products->total() }} {{ str('result')->plural($products->total()) }} found
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($products as $product)
                        <article class="transition hover:bg-gray-50 dark:hover:bg-[#202228]">
                            <a href="{{ $targetUrl($product) }}" wire:navigate class="grid grid-cols-[104px_minmax(0,1fr)] gap-3 p-3 sm:grid-cols-[150px_minmax(0,1fr)]">
                                <div class="aspect-square relative overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800">
                                    @if($product->image)
                                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full items-center justify-center">
                                            <x-icon name="o-photo" class="h-10 w-10 text-gray-300" />
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($product->isAuction())
                                            <span class="rounded-md bg-emerald-600 px-2 py-0.5 text-[10px] font-black text-white flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                                🔨 LIVE AUCTION
                                            </span>
                                        @else
                                            <span class="rounded-md bg-sky-600 px-2 py-0.5 text-[10px] font-black text-white">
                                                🏷️ DIRECT SELL
                                            </span>
                                        @endif

                                        <span class="rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest text-[#0C8FE8] dark:bg-blue-950/40">
                                            {{ $product->category?->name ?? 'Uncategorized' }}
                                        </span>
                                        <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest text-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                            {{ $conditionLabel($product->condition) }}
                                        </span>
                                    </div>

                                    <h2 class="mt-2 line-clamp-2 text-lg font-black text-gray-950 dark:text-gray-100">{{ $product->name }}</h2>
                                    <p class="mt-1.5 line-clamp-2 font-semibold text-gray-500">{{ $product->description }}</p>

                                    <div class="mt-3 flex flex-wrap items-center gap-3">
                                        <span class="ui-price font-black text-[#0C8FE8]">
                                            {{ $product->isAuction() ? 'Current Bid: ' : '' }}Rs {{ number_format((float) $priceFor($product)) }}
                                        </span>
                                        <span class="font-semibold text-gray-500">{{ $product->user?->name ?? 'Seller' }}</span>
                                        @if($product->location)
                                            <span class="font-semibold text-gray-500">{{ $product->location }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </article>
                    @empty
                        <div class="p-14 text-center">
                            <x-icon name="o-magnifying-glass" class="mx-auto h-10 w-10 text-gray-300" />
                            <p class="mt-4 text-sm font-bold text-gray-500">No products matched your search.</p>
                        </div>
                    @endforelse
                </div>

                <div class="p-3">{{ $products->links() }}</div>
            </div>
        </main>

        <aside class="hidden space-y-4 lg:block">
            <section class="rounded-md border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-[#181A1F]">
                <h2 class="ui-heading flex items-center gap-2 font-black">
                    <x-icon name="o-adjustments-horizontal" class="ui-icon-lg text-[#0C8FE8]" />
                    Filters
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="ui-small font-black uppercase tracking-widest text-gray-500">Price Range</label>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model.live.debounce.500ms="minPrice"
                                placeholder="Min"
                                class="h-10 min-w-0 rounded-md border border-gray-200 bg-gray-50 px-3 font-semibold outline-none focus:border-[#0C8FE8] dark:border-gray-800 dark:bg-[#202228]"
                            >
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model.live.debounce.500ms="maxPrice"
                                placeholder="Max"
                                class="h-10 min-w-0 rounded-md border border-gray-200 bg-gray-50 px-3 font-semibold outline-none focus:border-[#0C8FE8] dark:border-gray-800 dark:bg-[#202228]"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="ui-small font-black uppercase tracking-widest text-gray-500">Negotiable</label>
                        <div class="mt-2 space-y-2">
                            @foreach($negotiabilityOptions as $negotiabilityOption)
                                <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 font-bold dark:border-gray-800">
                                    <input type="radio" wire:model.live="negotiable" value="{{ $negotiabilityOption->value }}" class="radio radio-primary radio-sm">
                                    <span>{{ $negotiabilityOption->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </section>
</div>
