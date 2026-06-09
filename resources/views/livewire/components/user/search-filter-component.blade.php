<div>
    <section class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-[#15171B]">
        <div class="mx-auto flex max-w-[1480px] flex-col gap-3 px-3 py-3 lg:px-4">
            <form @submit.prevent="$store.searchProductStore.searchProduct()">
                <div class="grid grid-cols-1 gap-2 lg:grid-cols-[280px_minmax(0,1fr)_180px]">
                    <button type="button"
                            class="flex h-11 items-center justify-between rounded-md border border-gray-200 bg-gray-50 px-4 font-black dark:border-gray-800 dark:bg-[#202228] lg:hidden">
                        <span class="flex items-center gap-2"><x-icon name="o-squares-2x2" class="ui-icon-lg"/> All Categories</span>
                        <x-icon name="o-chevron-down" class="ui-icon"/>
                    </button>

                    <div
                        class="hidden h-11 items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-4 font-black dark:border-gray-800 dark:bg-[#202228] lg:flex">
                        <x-icon name="o-squares-2x2" class="ui-icon-lg text-[#0C8FE8]"/>
                        All Categories
                    </div>

                    <label
                        class="flex h-11 min-w-0 items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-4 focus-within:border-[#0C8FE8] dark:border-gray-800 dark:bg-[#202228]">
                        <x-icon name="o-magnifying-glass" class="ui-icon-lg text-gray-500"/>
                        <input type="search" placeholder="Search for products, auctions, bikes, phones..."
                               class="min-w-0 flex-1 bg-transparent font-semibold outline-none placeholder:text-gray-400"
                               x-model="$store.searchProductStore.search">
                    </label>

                    <button type="submit"
                            class="hidden h-11 items-center justify-center gap-2 rounded-md border border-gray-200 bg-gray-50 font-black hover:border-[#0C8FE8] hover:text-[#0C8FE8] dark:border-gray-800 dark:bg-[#202228] lg:flex">
                        <x-icon name="o-map-pin" class="ui-icon"/>
                        Nepal
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

@script
<script>
    Alpine.store('searchProductStore', {
        search: @js($search),
        category: @js($category),


        searchProduct() {
            let params = new URLSearchParams();
            if (this.search) {
                params.append('search', this.search);
            }
             if (this.category) {
                params.append('category', this.category);
            }
            window.location.href = '{{ route('user.search.product') }}' + '?' + params.toString();
        },
    })
</script>
@endscript
