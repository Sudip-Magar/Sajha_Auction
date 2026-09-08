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
                    @if(($order->status === 'confirmed' || $order->status === 'meetup_scheduled') && Auth::id() === $order->seller_id)
                        <button type="button" wire:click="updateOrderStatus('completed')" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                            Mark Completed & Handed Over
                        </button>
                    @endif
                    @if($order->status !== 'completed' && $order->status !== 'cancelled')
                        <button type="button" wire:click="toggleCancelForm" class="rounded-xl bg-rose-100 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-200 dark:bg-rose-950 dark:text-rose-300">
                            {{ $showCancelForm ? 'Never Mind' : 'Cancel Order' }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- Cancellation Reason Form --}}
            @if($showCancelForm)
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50/60 p-5 dark:border-rose-900 dark:bg-rose-950/20">
                    <h3 class="font-bold text-sm text-rose-900 dark:text-rose-300 mb-3">Why are you cancelling this order?</h3>

                    @if(Auth::id() === $order->buyer_id)
                        <div class="space-y-2 mb-4">
                            @foreach (\App\Services\OrderCancellationService::BUYER_REASONS as $value => $label)
                                <label class="flex items-center gap-2 text-xs font-semibold text-gray-800 dark:text-gray-200">
                                    <input type="radio" wire:model="cancelReasonCategory" value="{{ $value }}" class="text-rose-600 focus:ring-rose-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @if($order->deposit_status === 'paid')
                            <p class="text-xs text-amber-800 dark:text-amber-400 mb-3">
                                Note: your paid deposit is refunded if the item had a genuine defect/mismatch, but forfeited if you simply changed your mind.
                            </p>
                        @endif
                    @endif

                    <textarea wire:model="cancelNote" rows="2" placeholder="Optional details (e.g. what was wrong with the item)"
                              class="w-full rounded-xl border border-gray-200 p-3 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>

                    <div class="mt-3 flex justify-end gap-2">
                        <button type="button" wire:click="toggleCancelForm" class="rounded-xl px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
                            Never Mind
                        </button>
                        <button type="button" wire:click="confirmCancel" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700">
                            Confirm Cancellation
                        </button>
                    </div>
                </div>
            @endif

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

            {{-- Auction Win Deposit --}}
            @if($order->deposit_status !== 'not_required')
                @php
                    $depositCardClass = match($order->deposit_status) {
                        'paid' => 'bg-emerald-50/60 border-emerald-100 dark:bg-emerald-950/20 dark:border-emerald-900',
                        'refund_owed' => 'bg-blue-50/60 border-blue-100 dark:bg-blue-950/20 dark:border-blue-900',
                        'forfeited' => 'bg-rose-50/60 border-rose-100 dark:bg-rose-950/20 dark:border-rose-900',
                        default => 'bg-amber-50/60 border-amber-100 dark:bg-amber-950/20 dark:border-amber-900',
                    };
                    $depositBadge = match($order->deposit_status) {
                        'paid' => ['PAID via eSewa', 'text-emerald-700 dark:text-emerald-400'],
                        'refund_owed' => ['REFUND OWED TO BUYER', 'text-blue-700 dark:text-blue-400'],
                        'forfeited' => ['FORFEITED', 'text-rose-700 dark:text-rose-400'],
                        default => ['PENDING', 'text-amber-700 dark:text-amber-400'],
                    };
                @endphp
                <div class="mt-6 rounded-2xl border p-4 {{ $depositCardClass }}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-xs uppercase tracking-wider {{ $depositBadge[1] }}">
                                Auction Win Deposit
                            </h3>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                                Rs. {{ number_format($order->deposit_amount, 2) }}
                                <span class="ml-1 {{ $depositBadge[1] }} text-xs font-extrabold">{{ $depositBadge[0] }}</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Secures your auction win. The remaining Rs. {{ number_format($order->total_amount - $order->deposit_amount, 2) }} is paid in cash at the meetup after you inspect the item.
                            </p>
                            @if($order->deposit_status === 'refund_owed')
                                <p class="text-xs text-blue-800 dark:text-blue-300 mt-1 font-semibold">
                                    This deposit needs to be refunded to the buyer outside the app (eSewa refunds aren't automated here).
                                </p>
                            @endif
                        </div>
                        @if($order->needsDeposit() && Auth::id() === $order->buyer_id)
                            <a href="{{ route('payment.esewa.initiate', $order) }}" class="shrink-0 rounded-xl bg-[#60BB46] px-5 py-2.5 text-xs font-extrabold text-white hover:opacity-90 text-center">
                                Pay Deposit via eSewa
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Cancellation Reason (shown once cancelled) --}}
            @if($order->status === 'cancelled' && ($order->cancellation_reason_category || $order->cancellation_note))
                <div class="mt-6 rounded-2xl bg-gray-50 p-4 border border-gray-200 text-xs text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                    @if($order->cancellation_reason_category)
                        <p><strong>Cancellation Reason:</strong> {{ \App\Services\OrderCancellationService::BUYER_REASONS[$order->cancellation_reason_category] ?? $order->cancellation_reason_category }}</p>
                    @endif
                    @if($order->cancellation_note)
                        <p class="mt-1"><strong>Note:</strong> {{ $order->cancellation_note }}</p>
                    @endif
                </div>
            @endif

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
