<div class="space-y-6">
    <x-header title="Users" subtitle="Manage account status, seller access, and auction eligibility" separator progress-indicator />

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Users</p>
            <p class="mt-2 text-3xl font-black text-gray-900">{{ $totalUsers }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Active</p>
            <p class="mt-2 text-3xl font-black text-emerald-600">{{ $activeUsers }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Inactive</p>
            <p class="mt-2 text-3xl font-black text-rose-600">{{ $inactiveUsers }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Sellers</p>
            <p class="mt-2 text-3xl font-black text-sky-600">{{ $sellerUsers }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Auction Access</p>
            <p class="mt-2 text-3xl font-black text-amber-500">{{ $auctionUsers }}</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/30">
            <div class="flex items-center gap-2">
                <div class="w-1 bg-[#2FA084] h-6 rounded-full"></div>
                <h3 class="font-black text-gray-800 uppercase tracking-tighter">User Directory</h3>
            </div>

            <div class="w-full md:w-80">
                <x-input placeholder="Search users..." wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" class="bg-white" />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'name', 'label' => 'User'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'is_seller', 'label' => 'Seller'],
                ['key' => 'is_auction_allowed', 'label' => 'Auction'],
                ['key' => 'products_count', 'label' => 'Products'],
                ['key' => 'document_images_count', 'label' => 'Documents'],
                ['key' => 'actions', 'label' => '', 'sortable' => false],
            ];
        @endphp

        <x-table :headers="$headers" :rows="$users" with-pagination>
            @scope('cell_name', $user)
                <div class="flex items-center gap-4 py-1">
                    <div class="w-12 h-12 rounded-xl overflow-hidden border border-gray-100 shadow-inner bg-gray-50 flex items-center justify-center shrink-0">
                        @if($user->avatar)
                            <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                        @else
                            <span class="text-sm font-black text-gray-500">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <div class="font-black text-gray-900 truncate">{{ $user->name }}</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest truncate">{{ $user->email }}</div>
                    </div>
                </div>
            @endscope

            @scope('cell_status', $user)
                <x-badge :value="ucfirst($user->status)" :class="$user->isActiveStatus() ? 'badge-success' : 'badge-error'" class="font-bold text-[10px] uppercase tracking-wider" />
            @endscope

            @scope('cell_is_seller', $user)
                <x-badge :value="$user->is_seller ? 'Seller' : 'User'" :class="$user->is_seller ? 'badge-info' : 'badge-ghost'" class="font-bold text-[10px] uppercase tracking-wider" />
            @endscope

            @scope('cell_is_auction_allowed', $user)
                <x-badge :value="$user->is_auction_allowed ? 'Allowed' : 'Blocked'" :class="$user->is_auction_allowed ? 'badge-warning' : 'badge-neutral'" class="font-bold text-[10px] uppercase tracking-wider" />
            @endscope

            @scope('cell_products_count', $user)
                <span class="text-sm font-black text-gray-900">{{ $user->products_count }}</span>
            @endscope

            @scope('cell_document_images_count', $user)
                <span class="text-sm font-black text-gray-900">{{ $user->document_images_count }}</span>
            @endscope

            @scope('actions', $user)
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.users.show', $user) }}" wire:navigate>
                        <x-button label="View" icon="o-eye" class="btn-sm btn-info rounded-xl shadow-md shadow-info/20" />
                    </a>

                    <x-button
                        :label="$user->isActiveStatus() ? 'Inactive' : 'Active'"
                        icon="o-power"
                        :class="$user->isActiveStatus() ? 'btn-sm btn-error rounded-xl shadow-md shadow-error/20' : 'btn-sm btn-success rounded-xl shadow-md shadow-success/20'"
                        wire:click="toggleStatus({{ $user->id }})"
                        spinner
                    />
                </div>
            @endscope
        </x-table>
    </div>
</div>
