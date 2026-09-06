<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
    <x-header title="User Dashboard" subtitle="Track your listings, approvals, and auction activity" separator progress-indicator>
        <x-slot:actions>
            @if($user?->is_seller)
                <a href="{{ route('user.products') }}" wire:navigate>
                    <x-button label="Manage Products" icon="o-shopping-bag" class="btn-primary shadow-lg shadow-primary/20" />
                </a>
            @elseif($user?->seller_application_pending)
                <x-badge value="Seller application pending review" class="badge-warning font-bold" />
            @else
                <x-badge value="Buyer account" class="badge-ghost font-bold" />
            @endif
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm dark:bg-[#181A1F] dark:border-gray-800">
            <p class="text-xs uppercase tracking-widest text-gray-400 font-black dark:text-gray-500">Total Products</p>
            <p class="text-3xl font-black text-gray-900 mt-2 dark:text-gray-100">{{ $totalProducts }}</p>
        </div>
        <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm dark:bg-[#181A1F] dark:border-gray-800">
            <p class="text-xs uppercase tracking-widest text-gray-400 font-black dark:text-gray-500">Live Listings</p>
            <p class="text-3xl font-black text-green-600 mt-2 dark:text-green-400">{{ $activeProducts }}</p>
        </div>
        <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm dark:bg-[#181A1F] dark:border-gray-800">
            <p class="text-xs uppercase tracking-widest text-gray-400 font-black dark:text-gray-500">Pending Approval</p>
            <p class="text-3xl font-black text-amber-500 mt-2 dark:text-amber-400">{{ $pendingProducts }}</p>
        </div>
        <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm dark:bg-[#181A1F] dark:border-gray-800">
            <p class="text-xs uppercase tracking-widest text-gray-400 font-black dark:text-gray-500">Auction Items</p>
            <p class="text-3xl font-black text-sky-600 mt-2 dark:text-sky-400">{{ $auctionProducts }}</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden dark:bg-[#181A1F] dark:border-gray-800">
        <div class="p-6 border-b border-gray-50 dark:border-gray-800">
            <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">Recent Products</h3>
            <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">Your most recent listings and their current status.</p>
        </div>

        @if($recentProducts->isNotEmpty())
            <div class="divide-y divide-gray-50 dark:divide-gray-800">
                @foreach($recentProducts as $product)
                    <div class="p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 truncate dark:text-gray-100">{{ $product->name }}</p>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mt-1 dark:text-gray-500">
                                {{ $product->category?->name ?? 'Uncategorized' }} • {{ $product->listing_type->label() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-badge :value="$product->is_approved ? 'Approved' : 'Pending'" :class="$product->is_approved ? 'badge-success' : 'badge-warning'" />
                            <x-badge :value="ucfirst($product->status)" class="badge-ghost" />
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center">
                <x-icon name="o-shopping-bag" class="w-12 h-12 text-gray-200 mx-auto mb-4 dark:text-gray-700" />
                <p class="text-gray-500 font-semibold dark:text-gray-400">No products uploaded yet.</p>
                @if($user?->is_seller)
                    <a href="{{ route('user.products') }}" wire:navigate class="inline-block mt-4">
                        <x-button label="Upload First Product" icon="o-plus" class="btn-primary" />
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
