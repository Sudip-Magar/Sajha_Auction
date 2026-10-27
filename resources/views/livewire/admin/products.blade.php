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
                ['key' => 'listing_type', 'label' => 'Type'],
                ['key' => 'price_display', 'label' => 'Price/Start Bid'],
                ['key' => 'approval_status', 'label' => 'Status'],
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

            @scope('cell_listing_type', $product)
                <x-badge :value="$product->listing_type->value === 'auction' ? 'Auction' : 'Direct-Sell'"
                    :class="$product->listing_type->value === 'auction' ? 'badge-secondary' : 'badge-neutral'"
                    class="font-bold text-[10px] uppercase tracking-wider" />
            @endscope

            @scope('cell_price_display', $product)
                <span class="font-bold text-gray-900 text-sm">
                    @if($product->listing_type->value === 'direct_seller')
                        Rs. {{ number_format($product->sale_price) }}
                    @else
                        Rs. {{ number_format($product->auction?->traditionalAuction?->starting_bid) }}
                    @endif
                </span>
            @endscope

            @scope('cell_approval_status', $product)
                @php
                    $statusBadge = match($product->approval_status) {
                        \App\Enums\ProductApprovalStatus::APPROVED => 'badge-success',
                        \App\Enums\ProductApprovalStatus::REJECTED => 'badge-error',
                        \App\Enums\ProductApprovalStatus::CORRECTION => 'badge-warning',
                        default => 'badge-ghost',
                    };
                @endphp
                <x-badge :value="$product->approval_status->label()"
                    :class="$statusBadge"
                    class="font-bold text-[10px] uppercase tracking-wider" />
            @endscope

            @scope('actions', $product)
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.products.show', $product) }}" wire:navigate>
                        <x-button label="View" icon="o-eye" class="btn-sm btn-info rounded-xl shadow-md shadow-info/20" />
                    </a>
                    @if(in_array($product->approval_status, [\App\Enums\ProductApprovalStatus::PENDING, \App\Enums\ProductApprovalStatus::CORRECTION], true))
                        <x-button label="Approve" icon="o-check" class="btn-sm btn-success rounded-xl shadow-md shadow-success/20"
                            wire:click="approveProduct({{ $product->id }})" spinner />
                        <x-button label="Decide" icon="o-pencil-square" class="btn-sm rounded-xl"
                            wire:click="openRejectForm({{ $product->id }})" />
                    @endif
                </div>
            @endscope
        </x-table>
    </div>

    {{-- Reject / Request Correction --}}
    <x-modal wire:model="showDecisionModal" title="Reject or Request Correction" separator class="backdrop-blur-sm">
        <x-textarea label="Reason" wire:model="decisionReason" placeholder="Explain why, so the seller knows what to fix or why it was refused..." rows="4" />
        <x-slot:actions>
            <div class="flex flex-wrap justify-end gap-2 w-full">
                <x-button label="Cancel" wire:click="cancelDecision" class="rounded-xl" />
                <x-button label="Request Correction" wire:click="requestCorrection" class="btn-warning rounded-xl" spinner="requestCorrection" />
                <x-button label="Reject" wire:click="rejectProduct" class="btn-error rounded-xl" spinner="rejectProduct" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>
