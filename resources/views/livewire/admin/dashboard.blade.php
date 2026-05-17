<div class="space-y-8">
    <x-header title="Admin Dashboard" subtitle="Monitor platform activity and pending approvals" separator progress-indicator />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5">
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-[11px] text-gray-400 font-black uppercase tracking-widest">Users</p>
            <p class="text-3xl font-black text-gray-900 mt-2">{{ $totalUsers }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-[11px] text-gray-400 font-black uppercase tracking-widest">Sellers</p>
            <p class="text-3xl font-black text-emerald-600 mt-2">{{ $totalSellers }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-[11px] text-gray-400 font-black uppercase tracking-widest">Seller Requests</p>
            <p class="text-3xl font-black text-amber-500 mt-2">{{ $pendingSellerRequests }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-[11px] text-gray-400 font-black uppercase tracking-widest">Pending Products</p>
            <p class="text-3xl font-black text-sky-600 mt-2">{{ $pendingProducts }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-[11px] text-gray-400 font-black uppercase tracking-widest">Categories</p>
            <p class="text-3xl font-black text-violet-600 mt-2">{{ $totalCategories }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                <h3 class="font-black text-gray-900">Recent Seller Requests</h3>
                <a href="{{ route('admin.seller-requests') }}" wire:navigate class="text-xs font-bold text-[#2FA084] uppercase tracking-wider">View all</a>
            </div>
            @if($recentSellerRequests->isNotEmpty())
                <div class="divide-y divide-gray-50">
                    @foreach($recentSellerRequests as $user)
                        <div class="p-5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 truncate">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                            </div>
                            <x-badge value="Pending" class="badge-warning" />
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-sm text-gray-400">No pending seller requests.</div>
            @endif
        </div>

        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                <h3 class="font-black text-gray-900">Recent Pending Products</h3>
                <a href="{{ route('admin.products') }}" wire:navigate class="text-xs font-bold text-[#2FA084] uppercase tracking-wider">View all</a>
            </div>
            @if($recentPendingProducts->isNotEmpty())
                <div class="divide-y divide-gray-50">
                    @foreach($recentPendingProducts as $product)
                        <div class="p-5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 truncate">{{ $product->name }}</p>
                                <p class="text-xs text-gray-400 truncate">
                                    {{ $product->user?->name ?? 'Unknown seller' }} • {{ $product->category?->name ?? 'Uncategorized' }}
                                </p>
                            </div>
                            <x-badge value="Pending" class="badge-warning" />
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-sm text-gray-400">No pending products for review.</div>
            @endif
        </div>
    </div>
</div>
