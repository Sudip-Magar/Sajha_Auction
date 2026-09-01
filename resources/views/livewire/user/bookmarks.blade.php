@php
    $conditionLabel = fn ($condition) => str($condition)->replace('-', ' ')->title();
    $priceFor = function ($product) {
        if ($product->listing_type->value === 'auction' && $product->auction) {
            return $product->auction->current_price ?: $product->starting_bid;
        }

        return $product->sale_price;
    };
@endphp

<div class="marketplace-ui min-h-screen bg-[#F7F8FA] px-4 py-8 text-gray-950 dark:bg-[#101114] dark:text-gray-100 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <x-header title="Bookmarked Products" subtitle="Products you saved from the marketplace" separator progress-indicator>
            <x-slot:actions>
                <x-button label="Browse Products" icon="o-magnifying-glass" class="btn-primary" link="{{ route('home') }}" />
            </x-slot:actions>
        </x-header>

        <div class="mt-6 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-800 dark:bg-[#181A1F]">
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($products as $product)
                    <article class="relative transition hover:bg-gray-50 dark:hover:bg-[#202228]">
                        <a href="{{ route('user.products.show', $product->slug) }}" wire:navigate class="grid grid-cols-[104px_minmax(0,1fr)] gap-3 p-3 sm:grid-cols-[150px_minmax(0,1fr)]">
                            <div class="aspect-square overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center"><x-icon name="o-photo" class="h-10 w-10 text-gray-300" /></div>
                                @endif
                            </div>

                            <div class="min-w-0 pr-8">
                                <div class="flex items-start justify-between gap-4">
                                    <h2 class="ui-heading line-clamp-2 font-black">{{ $product->name }}</h2>
                                    <span class="hidden shrink-0 font-semibold text-gray-500 md:inline">Saved {{ $product->pivot->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-1.5 line-clamp-2 font-semibold text-gray-500">{{ $product->description }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="ui-price font-black text-[#0C8FE8]">Rs {{ number_format((float) $priceFor($product)) }}</span>
                                    <span class="rounded-md bg-gray-100 px-2 py-0.5 font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $conditionLabel($product->condition) }}</span>
                                    @if($product->listing_type->value === 'auction')
                                        <span class="rounded-md bg-emerald-100 px-2 py-0.5 font-black text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">Auction</span>
                                    @endif
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-3 font-semibold text-gray-500">
                                    <span class="truncate">{{ $product->location ?: $product->category?->name ?: 'Sajha Auction' }}</span>
                                    <span class="md:hidden">Saved {{ $product->pivot->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-2 truncate font-semibold text-gray-500">{{ $product->user?->name ?? 'Seller' }}</p>
                            </div>
                        </a>

                        <button
                            type="button"
                            wire:click="removeBookmark({{ $product->id }})"
                            wire:loading.attr="disabled"
                            wire:target="removeBookmark({{ $product->id }})"
                            class="absolute bottom-4 right-3 rounded-md p-1 text-[#0C8FE8] hover:bg-gray-100 disabled:opacity-60 dark:hover:bg-gray-800"
                            aria-label="Remove {{ $product->name }} from bookmarks"
                        >
                            <x-icon name="s-bookmark" class="ui-icon-lg" />
                        </button>
                    </article>
                @empty
                    <div class="p-14 text-center">
                        <x-icon name="o-bookmark" class="mx-auto h-10 w-10 text-gray-300" />
                        <p class="mt-4 text-sm font-bold text-gray-500">You have not bookmarked any products yet.</p>
                        <a href="{{ route('home') }}" wire:navigate class="mt-5 inline-flex items-center justify-center rounded-md bg-[#0C8FE8] px-4 py-2 font-black text-white">Browse Products</a>
                    </div>
                @endforelse
            </div>

            <div class="p-3">{{ $products->links() }}</div>
        </div>
    </div>
</div>
