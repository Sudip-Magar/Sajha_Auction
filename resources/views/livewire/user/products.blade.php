<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <x-header title="My Products" subtitle="Manage your auction and sales items" separator progress-indicator>
        @if($isSeller)
            <x-slot:actions>
                <x-button label="Upload Product" icon="o-plus" class="btn-primary shadow-lg shadow-primary/20" link="{{ route('user.products.create') }}" />
            </x-slot:actions>
        @endif
    </x-header>

    @if(!$isSeller)
        <div class="bg-linear-to-br from-emerald-50 via-white to-teal-50 border border-emerald-100 rounded-3xl p-8 sm:p-12 text-center shadow-xl max-w-3xl mx-auto my-8">
            <div class="w-20 h-20 bg-linear-to-br from-[#1F6F5F] to-[#2FA084] rounded-3xl flex items-center justify-center mx-auto mb-6 text-white shadow-xl shadow-[#2FA084]/30">
                <x-icon name="o-shopping-bag" class="w-10 h-10" />
            </div>

            <h2 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mb-3">Become a Seller on Sajha Auction</h2>
            <p class="text-gray-600 text-sm sm:text-base max-w-xl mx-auto mb-8 leading-relaxed">
                You are currently registered as a Buyer account. To upload products, host live auctions, or list items for direct sale, you need to apply for a Seller account.
            </p>

            @if($sellerApplicationPending)
                <div class="inline-flex items-center gap-3 px-6 py-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-700 font-bold text-sm">
                    <x-icon name="o-clock" class="w-5 h-5 text-amber-600 animate-pulse" />
                    <span>Seller Application Pending Admin Approval</span>
                </div>
            @else
                <x-button
                    label="Apply to Become a Seller"
                    icon="o-user-plus"
                    wire:click="requestSellerAccess"
                    wire:loading.attr="disabled"
                    wire:target="requestSellerAccess"
                    class="btn-primary bg-linear-to-r from-[#1F6F5F] to-[#2FA084] text-white px-8 py-3.5 h-auto text-base font-black rounded-2xl shadow-xl shadow-[#2FA084]/20 hover:scale-105 transition-transform"
                />
            @endif
        </div>
    @else
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
            @php
                $headers = [
                    ['key' => 'id', 'label' => 'ID', 'class' => 'w-16'],
                    ['key' => 'name', 'label' => 'Product'],
                    ['key' => 'listing_type', 'label' => 'Type'],
                    ['key' => 'price_display', 'label' => 'Price/Start Bid'],
                    ['key' => 'is_approved', 'label' => 'Approval'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'actions', 'label' => '', 'sortable' => false],
                ];
            @endphp

            <x-table :headers="$headers" :rows="$products" @class(['table-auto w-full'])>
                @scope('cell_name', $product)
                    <div class="flex items-center gap-4 py-1">
                        <div class="w-12 h-12 rounded-xl overflow-hidden border border-gray-100 bg-gray-50 flex items-center justify-center shrink-0">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover" />
                            @else
                                <x-icon name="o-photo" class="w-6 h-6 text-gray-300" />
                            @endif
                        </div>
                        <div>
                            <div class="font-black text-gray-900">{{ $product->name }}</div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $product->category->name ?? 'Uncategorized' }}</div>
                        </div>
                    </div>
                @endscope

                @scope('cell_listing_type', $product)
                    @php($auctionType = $product->auction_type)
                    <div class="flex flex-col gap-1">
                        <x-badge :value="$product->listing_type->label()" :class="$product->listing_type->value === 'auction' ? 'badge-info text-white' : 'badge-primary text-white'" class="font-bold text-[10px] uppercase" />
                        @if($product->listing_type->value === 'auction' && $auctionType)
                            <span class="text-[9px] font-black uppercase text-gray-400 tracking-tighter">{{ $auctionType->label() }}</span>
                        @endif
                    </div>
                @endscope

                @scope('cell_price_display', $product)
                    <span class="font-bold text-gray-700">
                        @if($product->listing_type->value === 'direct_seller')
                            Rs. {{ number_format($product->sale_price) }}
                        @else
                            Rs. {{ number_format($product->auction->traditionalAuction?->starting_bid) }}
                        @endif
                    </span>
                @endscope

                @scope('cell_is_approved', $product)
                    <div class="flex items-center gap-2">
                        <div @class([
                            'w-2 h-2 rounded-full',
                            'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]' => $product->is_approved,
                            'bg-yellow-400 shadow-[0_0_8px_rgba(250,204,21,0.5)]' => !$product->is_approved,
                        ])></div>
                        <span @class([
                            'text-[10px] font-black uppercase tracking-tighter',
                            'text-green-600' => $product->is_approved,
                            'text-yellow-600' => !$product->is_approved,
                        ])>{{ $product->is_approved ? 'Approved' : 'Pending' }}</span>
                    </div>
                @endscope

                @scope('cell_status', $product)
                    <x-badge :value="ucfirst($product->status)" @class([
                        'font-bold text-[10px] uppercase',
                        'badge-success text-white' => $product->status === 'active',
                        'badge-warning text-white' => $product->status === 'pending',
                        'badge-ghost' => !in_array($product->status, ['active', 'pending']),
                    ]) />
                @endscope

                @scope('actions', $product)
                    <div class="flex items-center gap-2 justify-end">
                        @if(!$product->is_approved)
                            <x-button label="Edit" icon="o-pencil-square" class="btn-sm btn-ghost" link="{{ route('user.products.edit', $product->id) }}" />
                            <x-button label="Delete" icon="o-trash" class="btn-sm btn-ghost text-red-500 hover:bg-red-50"
                                wire:click="deleteProduct({{ $product->id }})"
                                wire:confirm="Are you sure you want to delete this product? All of its images will also be permanently deleted from the system." />
                        @else
                            @if($product->isAuction() && $product->auction)
                                <x-button label="Live Auction Room" icon="o-ticket" class="btn-xs btn-primary bg-emerald-600 border-none text-white font-bold" link="{{ route('user.auction.detail', $product->auction->id) }}" />
                            @else
                                <x-button label="View Details" icon="o-eye" class="btn-xs btn-ghost text-gray-500" link="{{ route('user.products.show', $product->slug) }}" />
                            @endif
                        @endif
                    </div>
                @endscope
            </x-table>
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif
</div>
