<div>
    <x-header title="Complaints" subtitle="Buyer complaints (item damaged, not as described, documents missing) awaiting or already given a verdict" separator progress-indicator />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-gray-50 text-gray-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-exclamation-triangle" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Complaints</p>
                <p class="text-2xl font-black text-gray-900">{{ $totalComplaints }}</p>
            </div>
        </div>
        <button type="button" wire:click="$set('statusFilter', 'under_review')" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4 text-left hover:border-amber-300 transition-colors">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-clock" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Awaiting Verdict</p>
                <p class="text-2xl font-black text-gray-900">{{ $underReviewCount }}</p>
            </div>
        </button>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-hand-thumb-down" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Confirmed Damaged</p>
                <p class="text-2xl font-black text-gray-900">{{ $confirmedDamagedCount }}</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-hand-thumb-up" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Not Damaged</p>
                <p class="text-2xl font-black text-gray-900">{{ $notDamagedCount }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-gray-50/30">
            <div class="flex items-center gap-2">
                <div class="w-1 bg-[#2FA084] h-6 rounded-full"></div>
                <h3 class="font-black text-gray-800 uppercase tracking-tighter">Complaint Queue</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full min-w-0 xl:max-w-[36rem] xl:flex-1">
                <x-input placeholder="Search order #, buyer, seller, product..." wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" class="bg-white" />
                <x-select wire:model.live="statusFilter" :options="$statusOptions" placeholder="All Statuses" icon="o-flag" class="bg-white" />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'order_number', 'label' => 'Order'],
                ['key' => 'product', 'label' => 'Product'],
                ['key' => 'buyer', 'label' => 'Buyer'],
                ['key' => 'seller', 'label' => 'Seller'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'actions', 'label' => '', 'sortable' => false],
            ];
        @endphp

        <x-table :headers="$headers" :rows="$complaints" with-pagination>
            @scope('cell_order_number', $order)
                <div class="font-black text-gray-900 text-sm">{{ $order->order_number }}</div>
                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $order->created_at->format('M d, Y') }}</div>
            @endscope

            @scope('cell_product', $order)
                @php $product = $order->items->first()?->product; @endphp
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl overflow-hidden border border-gray-100 shadow-inner bg-gray-50 flex items-center justify-center shrink-0">
                        @if($product?->image)
                            <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover" />
                        @else
                            <x-icon name="o-photo" class="w-5 h-5 text-gray-300" />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-gray-900 text-xs truncate max-w-[10rem]">{{ $product?->name ?? 'Product Unavailable' }}</div>
                        <span class="text-[9px] uppercase font-black tracking-tighter {{ $order->auction_id ? 'text-purple-500' : 'text-gray-400' }}">
                            {{ $order->auction_id ? 'Auction Win' : 'Direct Sell' }}
                        </span>
                    </div>
                </div>
            @endscope

            @scope('cell_buyer', $order)
                <p class="font-bold text-gray-800 text-xs truncate max-w-[8rem]">{{ $order->buyer?->name ?? 'Unknown' }}</p>
            @endscope

            @scope('cell_seller', $order)
                <p class="font-bold text-gray-800 text-xs truncate max-w-[8rem]">{{ $order->seller?->name ?? 'Unknown' }}</p>
            @endscope

            @scope('cell_status', $order)
                @php
                    $statusBadge = match($order->complaint_status) {
                        \App\Enums\OrderComplaintStatus::UNDER_REVIEW => ['Awaiting Verdict', 'bg-amber-100 text-amber-700'],
                        \App\Enums\OrderComplaintStatus::CONFIRMED_DAMAGED => ['Confirmed Damaged', 'bg-rose-100 text-rose-700'],
                        \App\Enums\OrderComplaintStatus::NOT_DAMAGED => ['Not Damaged', 'bg-gray-100 text-gray-700'],
                        default => ['Unknown', 'bg-gray-100 text-gray-500'],
                    };
                @endphp
                <span class="inline-flex rounded-full px-3 py-1 text-[10px] font-extrabold uppercase {{ $statusBadge[1] }}">{{ $statusBadge[0] }}</span>
            @endscope

            @scope('actions', $order)
                <a href="{{ route('admin.complaints.show', $order) }}" wire:navigate>
                    <x-button label="View" icon="o-eye" class="btn-sm btn-info rounded-xl shadow-md shadow-info/20" />
                </a>
            @endscope
        </x-table>

        @if($complaints->isEmpty())
            <div class="py-20 text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-icon name="o-face-smile" class="w-10 h-10 text-gray-200" />
                </div>
                <h3 class="text-gray-400 font-bold uppercase tracking-widest text-sm">No complaints found</h3>
            </div>
        @endif
    </div>
</div>
