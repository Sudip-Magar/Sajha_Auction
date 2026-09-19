<div class="marketplace-ui min-h-screen bg-[#F7F8FA] text-gray-950 dark:bg-gray-900 dark:text-gray-100">
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-header title="Products" subtitle="Approved direct-seller listings" separator progress-indicator />

        @if($products->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center dark:border-gray-800 dark:bg-[#181A1F]">
                <x-icon name="o-shopping-bag" class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-700" />
                <p class="mt-4 text-sm font-bold text-gray-500 dark:text-gray-400">No approved direct-seller products are available.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach($products as $product)
                    <a href="{{ route('user.products.show', $product->slug) }}" wire:navigate class="block overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:border-[#1F6F5F] dark:border-gray-800 dark:bg-[#181A1F] dark:shadow-none">
                        <div class="aspect-[4/3] bg-gray-50 dark:bg-gray-800">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover" />
                            @else
                                <div class="flex h-full items-center justify-center">
                                    <x-icon name="o-photo" class="h-10 w-10 text-gray-300 dark:text-gray-700" />
                                </div>
                            @endif
                        </div>

                        <div class="p-4 space-y-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-[#1F6F5F] dark:text-[#7CE0C5]">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                                <h2 class="mt-1 line-clamp-2 text-base font-black text-gray-900 dark:text-gray-100">{{ $product->name }}</h2>
                            </div>

                            <p class="line-clamp-2 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ str($product->description)->stripTags() }}</p>

                            <div class="flex items-end justify-between gap-3 border-t border-gray-100 pt-3 dark:border-gray-800">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Price</p>
                                    <p class="text-lg font-black text-gray-900 dark:text-gray-100">Rs. {{ number_format($product->sale_price) }}</p>
                                </div>
                                <x-badge :value="$product->stock_quantity . ' in stock'" class="badge-success text-white text-[10px] font-bold uppercase" />
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </section>
</div>
