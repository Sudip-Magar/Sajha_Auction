<div>
    <x-header title="Orders" subtitle="All second-hand and auction orders across the platform" separator progress-indicator />

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-clipboard-document-list" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Orders</p>
                <p class="text-2xl font-black text-gray-900">{{ $totalOrders }}</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-check-circle" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Completed / Sold</p>
                <p class="text-2xl font-black text-gray-900">{{ $completedOrders }}</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-x-circle" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Cancelled</p>
                <p class="text-2xl font-black text-gray-900">{{ $cancelledOrders }}</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-banknotes" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Deposit Refunds Owed</p>
                <p class="text-2xl font-black text-gray-900">{{ $refundsOwed }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-gray-50/30">
            <div class="flex items-center gap-2">
                <div class="w-1 bg-[#2FA084] h-6 rounded-full"></div>
                <h3 class="font-black text-gray-800 uppercase tracking-tighter">Order Ledger</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 w-full xl:w-[46rem]">
                <x-input placeholder="Search order #, buyer, seller, product..." wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" class="bg-white" />
                <x-select wire:model.live="typeFilter" :options="$typeOptions" placeholder="All Types" icon="o-tag" class="bg-white" />
                <x-select wire:model.live="statusFilter" :options="$statusOptions" placeholder="All Statuses" icon="o-flag" class="bg-white" />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'order_number', 'label' => 'Order'],
                ['key' => 'product', 'label' => 'Product / Type'],
                ['key' => 'parties', 'label' => 'Buyer -> Seller'],
                ['key' => 'total_amount', 'label' => 'Amount'],
                ['key' => 'deposit', 'label' => 'Deposit'],
                ['key' => 'status', 'label' => 'Status'],
            ];
        @endphp

        <x-table :headers="$headers" :rows="$orders" with-pagination>
            @scope('cell_order_number', $order)
                <div>
                    <div class="font-black text-gray-900 text-sm">{{ $order->order_number }}</div>
                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $order->created_at->format('M d, Y') }}</div>
                </div>
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

            @scope('cell_parties', $order)
                <div class="text-xs">
                    <p class="font-bold text-gray-800">{{ $order->buyer?->name ?? 'Unknown' }}</p>
                    <p class="text-gray-400">&darr;</p>
                    <p class="font-bold text-gray-800">{{ $order->seller?->name ?? 'Unknown' }}</p>
                </div>
            @endscope

            @scope('cell_total_amount', $order)
                <span class="font-black text-gray-900 text-sm">Rs. {{ number_format($order->total_amount) }}</span>
            @endscope

            @scope('cell_deposit', $order)
                @if($order->deposit_status === 'not_required')
                    <span class="text-[10px] text-gray-300 font-bold uppercase">&mdash;</span>
                @else
                    @php
                        $depositBadge = match($order->deposit_status) {
                            'paid' => ['Paid', 'bg-emerald-100 text-emerald-800 border-emerald-300'],
                            'refund_owed' => ['Refund Owed', 'bg-blue-100 text-blue-800 border-blue-300'],
                            'forfeited' => ['Forfeited', 'bg-rose-100 text-rose-800 border-rose-300'],
                            default => ['Pending', 'bg-amber-100 text-amber-800 border-amber-300'],
                        };
                    @endphp
                    <div>
                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-extrabold {{ $depositBadge[1] }}">{{ $depositBadge[0] }}</span>
                        <p class="text-[10px] text-gray-400 mt-1 font-bold">Rs. {{ number_format($order->deposit_amount ?? 0) }}</p>
                    </div>
                @endif
            @endscope

            @scope('cell_status', $order)
                <div>
                    <span class="rounded-full border px-3 py-1 text-[10px] font-extrabold {{ $order->status_badge }}">
                        {{ $order->status_label }}
                    </span>
                    @if($order->status === 'cancelled' && $order->cancellation_reason_category)
                        <p class="text-[10px] text-gray-400 mt-1 max-w-[12rem] truncate" title="{{ $order->cancellation_note }}">
                            {{ \App\Services\OrderCancellationService::BUYER_REASONS[$order->cancellation_reason_category] ?? $order->cancellation_reason_category }}
                        </p>
                    @endif
                </div>
            @endscope
        </x-table>
    </div>
</div>
