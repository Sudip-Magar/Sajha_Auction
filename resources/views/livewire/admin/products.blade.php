<div>
    <x-header title="Product Approval" subtitle="Review and approve seller listings" separator progress-indicator />

    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/30">
            <div class="flex items-center gap-2">
                <div class="w-1 bg-[#2FA084] h-6 rounded-full"></div>
                <h3 class="font-black text-gray-800 uppercase tracking-tighter">Listing Queue</h3>
            </div>
            <div class="w-full md:w-80">
                <x-input placeholder="Filter products..." wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" class="bg-white" />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'id', 'label' => 'ID', 'class' => 'w-16 text-gray-400'],
                ['key' => 'name', 'label' => 'Product Details'],
                ['key' => 'user.name', 'label' => 'Seller'],
                ['key' => 'price_display', 'label' => 'Price/Start Bid'],
                ['key' => 'is_approved', 'label' => 'Status'],
                ['key' => 'actions', 'label' => '', 'sortable' => false],
            ];
        @endphp

        <x-table :headers="$headers" :rows="$products" with-pagination>
            @scope('cell_name', $product)
                <div class="flex items-center gap-4 py-1">
                    <div class="w-12 h-12 rounded-xl overflow-hidden border border-gray-100 shadow-inner bg-gray-50 flex items-center justify-center shrink-0">
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

            @scope('cell_user.name', $product)
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-[#1F6F5F] flex items-center justify-center text-[8px] text-white font-bold">
                        {{ substr($product->user->name ?? 'S', 0, 1) }}
                    </div>
                    <span class="text-xs font-bold text-gray-600">{{ $product->user->name ?? 'Unknown seller' }}</span>
                </div>
            @endscope

            @scope('cell_price_display', $product)
                @php($auctionType = $product->auction_type)
                <div class="flex flex-col">
                    <span class="font-bold text-gray-900 text-sm">
                        @if($product->type->value === 'direct_seller')
                            Rs. {{ number_format($product->sale_price) }}
                        @elseif($auctionType?->value === 'penny')
                            Rs. {{ number_format($product->starting_price_cents / 100, 2) }} (Penny)
                        @else
                            Rs. {{ number_format($product->starting_bid) }}
                        @endif
                    </span>
                    <span class="text-[9px] uppercase font-black text-gray-400 tracking-tighter">{{ $product->type->label() }}</span>
                </div>
            @endscope

            @scope('cell_is_approved', $product)
                <x-badge :value="$product->is_approved ? 'Live' : 'Pending Review'" 
                    :class="$product->is_approved ? 'badge-success' : 'badge-warning'" 
                    class="font-bold text-[10px] uppercase tracking-wider" />
            @endscope

            @scope('actions', $product)
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.products.show', $product) }}" wire:navigate>
                        <x-button label="View" icon="o-eye" class="btn-sm btn-info rounded-xl shadow-md shadow-info/20" />
                    </a>
                    @if(!$product->is_approved)
                        <x-button label="Approve" icon="o-check" class="btn-sm btn-success rounded-xl shadow-md shadow-success/20" 
                            wire:click="approveProduct({{ $product->id }})" spinner />
                    @endif
                </div>
            @endscope
        </x-table>
    </div>
</div>
