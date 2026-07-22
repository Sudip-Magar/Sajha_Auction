<div class="min-h-screen bg-gray-50/50 py-10 dark:bg-[#101114]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 pb-5 dark:border-gray-800">
            <div>
                <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                    <x-icon name="o-archive-box" class="w-8 h-8 text-[#1F6F5F]" />
                    Orders & Direct Sales
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Track your purchases, direct-sell handovers, and incoming sales
                </p>
            </div>

            {{-- Tabs --}}
            <div class="mt-4 sm:mt-0 flex rounded-2xl bg-gray-200/60 p-1 dark:bg-gray-800">
                <button type="button"
                        wire:click="$set('tab', 'purchases')"
                        @class([
                            'rounded-xl px-5 py-2 text-xs font-bold transition-all',
                            'bg-white text-gray-900 shadow-sm dark:bg-[#181A1F] dark:text-white' => $tab === 'purchases',
                            'text-gray-600 hover:text-gray-900 dark:text-gray-400' => $tab !== 'purchases'
                        ])>
                    My Purchases ({{ $purchases->count() }})
                </button>
                <button type="button"
                        wire:click="$set('tab', 'sales')"
                        @class([
                            'rounded-xl px-5 py-2 text-xs font-bold transition-all',
                            'bg-white text-gray-900 shadow-sm dark:bg-[#181A1F] dark:text-white' => $tab === 'sales',
                            'text-gray-600 hover:text-gray-900 dark:text-gray-400' => $tab !== 'sales'
                        ])>
                    My Sales / Incoming Orders ({{ $sales->count() }})
                </button>
            </div>
        </div>

        @php
            $currentOrders = $tab === 'purchases' ? $purchases : $sales;
        @endphp

        @if($currentOrders->isEmpty())
            <div class="mt-12 rounded-3xl border border-dashed border-gray-300 bg-white p-12 text-center shadow-xs dark:border-gray-800 dark:bg-[#181A1F]">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/30">
                    <x-icon name="o-archive-box-x-mark" class="h-8 w-8 text-[#2FA084]" />
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">
                    No {{ $tab === 'purchases' ? 'purchases' : 'sales orders' }} found
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $tab === 'purchases' ? 'You have not placed any orders yet.' : 'No buyers have placed direct-sell orders for your products yet.' }}
                </p>
                <div class="mt-6">
                    <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-[#1F6F5F] to-[#2FA084] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#2FA084]/20 hover:opacity-95">
                        <x-icon name="o-shopping-bag" class="w-4 h-4" />
                        Explore Marketplace
                    </a>
                </div>
            </div>
        @else
            <div class="mt-8 space-y-6">
                @foreach($currentOrders as $order)
                    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-xs transition-all hover:shadow-md dark:border-gray-800 dark:bg-[#181A1F]">
                        {{-- Header Info --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="font-black text-base text-gray-900 dark:text-white">
                                        Order #{{ $order->order_number }}
                                    </span>
                                    <span class="rounded-full border px-3 py-0.5 text-xs font-extrabold {{ $order->status_badge }}">
                                        {{ $order->status_label }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    Placed on {{ $order->created_at->format('M d, Y @ h:i A') }} ({{ $order->created_at->diffForHumans() }})
                                </p>
                            </div>

                            <div class="text-left sm:text-right">
                                <p class="text-sm font-bold text-gray-500">
                                    {{ $tab === 'purchases' ? 'Seller: '.$order->seller?->name : 'Buyer: '.$order->buyer?->name }}
                                </p>
                                <p class="text-xs text-gray-400">Phone: {{ $tab === 'purchases' ? ($order->seller?->phone ?? 'N/A') : ($order->buyer_phone ?: $order->buyer?->phone) }}</p>
                            </div>
                        </div>

                        {{-- Order Items --}}
                        <div class="mt-4 space-y-3">
                            @foreach($order->items as $item)
                                @php
                                    $prod = $item->product;
                                    $img = $prod?->image ? Storage::url($prod->image) : null;
                                @endphp
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="h-14 w-14 shrink-0 rounded-xl overflow-hidden bg-gray-100">
                                            @if($img)
                                                <img src="{{ $img }}" alt="{{ $prod?->name }}" class="h-full w-full object-cover">
                                            @else
                                                <div class="flex h-full items-center justify-center text-gray-400">
                                                    <x-icon name="o-photo" class="h-6 w-6" />
                                                </div>
                                            @endif
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <h4 class="font-bold text-sm text-gray-900 truncate dark:text-white">
                                                @if($prod)
                                                    <a href="{{ route('user.products.show', $prod->slug) }}" wire:navigate class="hover:underline">
                                                        {{ $prod->name }}
                                                    </a>
                                                @else
                                                    Product Unavailable
                                                @endif
                                            </h4>
                                            <p class="text-xs text-gray-500">Qty: {{ $item->quantity }} × Rs {{ number_format($item->price) }}</p>
                                        </div>
                                    </div>

                                    <div class="font-black text-sm text-gray-900 dark:text-white">
                                        Rs {{ number_format($item->subtotal) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Meetup Place & Handover Card --}}
                        <div class="mt-4 rounded-2xl bg-emerald-50/60 p-4 border border-emerald-100 dark:bg-emerald-950/20 dark:border-emerald-900 flex flex-col sm:flex-row justify-between gap-3 text-xs">
                            <div>
                                <p class="font-bold text-emerald-950 dark:text-emerald-300 flex items-center gap-1.5">
                                    <x-icon name="o-map-pin" class="w-4 h-4 text-emerald-600" />
                                    Handover Mode: {{ strtoupper($order->handover_type) }}
                                </p>

                                @if($order->handover_type === 'meetup')
                                    <p class="mt-1 text-emerald-800 dark:text-emerald-400">
                                        <span class="font-semibold">Meetup Location:</span> {{ $order->meetup_location ?: 'To be agreed between buyer and seller' }}
                                    </p>
                                    @if($order->meetup_time)
                                        <p class="text-emerald-800 dark:text-emerald-400">
                                            <span class="font-semibold">Scheduled Time:</span> {{ $order->meetup_time->format('M d, Y @ h:i A') }}
                                        </p>
                                    @endif
                                @else
                                    <p class="mt-1 text-emerald-800 dark:text-emerald-400">
                                        <span class="font-semibold">Shipping Address:</span> {{ $order->shipping_address }}
                                    </p>
                                @endif
                            </div>

                            <div>
                                <p class="font-bold text-emerald-950 dark:text-emerald-300">
                                    Payment: {{ str($order->payment_method)->replace('_', ' ')->title() }} ({{ str($order->payment_status)->title() }})
                                </p>
                                <p class="mt-1 font-black text-sm text-emerald-950 dark:text-emerald-200">
                                    Total: Rs {{ number_format($order->total_amount) }}
                                </p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                            <a href="{{ route('user.orders.show', $order->id) }}" wire:navigate
                               class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1F6F5F] hover:underline">
                                <x-icon name="o-eye" class="w-4 h-4" />
                                View Full Timeline & Order Details
                            </a>

                            <div class="flex items-center gap-2">
                                @if($order->status === 'pending')
                                    @if($tab === 'sales')
                                        <button type="button"
                                                wire:click="updateOrderStatus({{ $order->id }}, 'confirmed')"
                                                class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700">
                                            Confirm Order
                                        </button>
                                    @endif
                                    <button type="button"
                                            wire:click="updateOrderStatus({{ $order->id }}, 'cancelled')"
                                            class="rounded-xl bg-rose-100 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-200 dark:bg-rose-950 dark:text-rose-300">
                                        Cancel Order
                                    </button>
                                @elseif($order->status === 'confirmed' || $order->status === 'meetup_scheduled')
                                    <button type="button"
                                            wire:click="updateOrderStatus({{ $order->id }}, 'completed')"
                                            class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                                        Mark Completed & Handed Over
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
