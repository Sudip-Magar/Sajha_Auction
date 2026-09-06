<div class="min-h-screen bg-gray-50/50 py-10 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        {{-- Header & Back --}}
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('user.orders') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-bold text-gray-600 hover:text-[#1F6F5F] dark:text-gray-400">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                Back to Orders
            </a>
            <span class="rounded-full border px-3 py-1 text-xs font-extrabold {{ $order->status_badge }}">
                {{ $order->status_label }}
            </span>
        </div>

        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-[#181A1F]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-5 dark:border-gray-800">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                        <x-icon name="o-document-text" class="w-7 h-7 text-[#1F6F5F]" />
                        Order Details #{{ $order->order_number }}
                    </h1>
                    <p class="mt-1 text-xs text-gray-500">
                        Placed on {{ $order->created_at->format('M d, Y @ h:i A') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if($order->status === 'pending' && Auth::id() === $order->seller_id)
                        <button type="button" wire:click="updateOrderStatus('confirmed')" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700">
                            Confirm Order
                        </button>
                    @endif
                    @if(($order->status === 'confirmed' || $order->status === 'meetup_scheduled'))
                        <button type="button" wire:click="updateOrderStatus('completed')" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                            Mark Completed & Handed Over
                        </button>
                    @endif
                    @if($order->status !== 'completed' && $order->status !== 'cancelled')
                        <button type="button" wire:click="updateOrderStatus('cancelled')" class="rounded-xl bg-rose-100 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-200 dark:bg-rose-950 dark:text-rose-300">
                            Cancel Order
                        </button>
                    @endif
                </div>
            </div>

            {{-- People & Handover Info Grid --}}
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Buyer Info --}}
                <div class="rounded-2xl bg-gray-50 p-4 border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-gray-500 mb-2">Buyer Information</h3>
                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $order->buyer?->name }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Phone: {{ $order->buyer_phone ?: $order->buyer?->phone }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Email: {{ $order->buyer?->email }}</p>
                </div>

                {{-- Seller Info --}}
                <div class="rounded-2xl bg-gray-50 p-4 border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-gray-500 mb-2">Seller Information</h3>
                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $order->seller?->name }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Phone: {{ $order->seller?->phone ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Email: {{ $order->seller?->email }}</p>
                </div>

                {{-- Handover Details --}}
                <div class="rounded-2xl bg-emerald-50/60 p-4 border border-emerald-100 dark:bg-emerald-950/20 dark:border-emerald-900">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-emerald-800 dark:text-emerald-300 mb-2">
                        Handover & Location
                    </h3>
                    <p class="text-xs font-bold text-emerald-900 dark:text-emerald-200">
                        Mode: {{ strtoupper($order->handover_type) }}
                    </p>
                    @if($order->handover_type === 'meetup')
                        <p class="text-xs text-emerald-800 dark:text-emerald-300 mt-1">
                            <span class="font-semibold">Meetup Location:</span> {{ $order->meetup_location ?: 'Seller Location' }}
                        </p>
                        @if($order->meetup_time)
                            <p class="text-xs text-emerald-800 dark:text-emerald-300">
                                <span class="font-semibold">Time:</span> {{ $order->meetup_time->format('M d, Y @ h:i A') }}
                            </p>
                        @endif
                    @else
                        <p class="text-xs text-emerald-800 dark:text-emerald-300 mt-1">
                            <span class="font-semibold">Address:</span> {{ $order->shipping_address }}
                        </p>
                    @endif
                    <p class="text-xs font-bold text-emerald-900 dark:text-emerald-200 mt-2">
                        Payment: {{ str($order->payment_method)->replace('_', ' ')->title() }} ({{ str($order->payment_status)->title() }})
                    </p>
                </div>
            </div>

            {{-- Notes --}}
            @if($order->notes)
                <div class="mt-6 rounded-2xl bg-amber-50/60 p-4 border border-amber-100 text-xs text-amber-900">
                    <strong>Buyer Notes:</strong> {{ $order->notes }}
                </div>
            @endif

            {{-- Order Items Table --}}
            <div class="mt-8">
                <h3 class="text-lg font-black text-gray-900 dark:text-white mb-4">Ordered Products</h3>
                <div class="space-y-4">
                    @foreach($order->items as $item)
                        @php
                            $prod = $item->product;
                            $img = $prod?->image ? Storage::url($prod->image) : null;
                        @endphp
                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="h-16 w-16 shrink-0 rounded-xl overflow-hidden bg-gray-100">
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
                                        <p class="text-xs text-gray-500">
                                            Condition: {{ str($prod?->condition ?? 'Used')->replace('-', ' ')->title() }} | Qty: {{ $item->quantity }} × Rs {{ number_format($item->price) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="text-right font-black text-base text-gray-900 dark:text-white">
                                    Rs {{ number_format($item->subtotal) }}
                                </div>
                            </div>

                            {{-- Item Timeline Log --}}
                            @if($prod && $prod->timelines->isNotEmpty())
                                <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-800">
                                    <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Product Lifecycle & Timeline</h5>
                                    <div class="relative pl-6 space-y-3 before:absolute before:left-2 before:top-1 before:bottom-1 before:w-0.5 before:bg-gray-200 dark:before:bg-gray-800">
                                        @foreach($prod->timelines as $timeline)
                                            <div class="relative">
                                                <div class="absolute -left-6 top-1 h-3 w-3 rounded-full border-2 border-white bg-[#1F6F5F] dark:border-[#181A1F]"></div>
                                                <div class="flex items-baseline justify-between gap-2">
                                                    <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $timeline->title }}</p>
                                                    <span class="text-[10px] text-gray-400">{{ $timeline->created_at->format('M d, Y @ h:i A') }}</span>
                                                </div>
                                                @if($timeline->description)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $timeline->description }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
