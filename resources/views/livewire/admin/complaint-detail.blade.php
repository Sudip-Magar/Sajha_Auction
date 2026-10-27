<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <a href="{{ route('admin.complaints') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-bold text-gray-600 hover:text-[#1F6F5F] mb-4">
        <x-icon name="o-arrow-left" class="w-4 h-4" />
        Back to Complaints
    </a>

    <x-header title="Complaint on Order #{{ $order->order_number }}" subtitle="{{ $order->auction_id ? 'Auction order' : 'Second-hand order' }}" separator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Order & Product Summary --}}
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-black text-gray-800">Order Summary</h3>
                    <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="text-xs font-bold text-[#1F6F5F] hover:underline">
                        Open full order page &rarr;
                    </a>
                </div>

                @php $product = $order->items->first()?->product; @endphp
                <div class="flex items-center gap-4 mb-4 pb-4 border-b border-gray-50">
                    <div class="w-14 h-14 rounded-xl overflow-hidden border border-gray-100 shadow-inner bg-gray-50 flex items-center justify-center shrink-0">
                        @if($product?->image)
                            <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover" />
                        @else
                            <x-icon name="o-photo" class="w-6 h-6 text-gray-300" />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="font-black text-gray-900 text-sm truncate">{{ $product?->name ?? 'Product Unavailable' }}</div>
                        <span class="text-[9px] uppercase font-black tracking-tighter {{ $order->auction_id ? 'text-purple-500' : 'text-gray-400' }}">
                            {{ $order->auction_id ? 'Auction Win' : 'Direct Sell' }}
                        </span>
                    </div>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-400 text-xs font-bold uppercase">Order #</dt><dd class="font-semibold">{{ $order->order_number }}</dd></div>
                    <div><dt class="text-gray-400 text-xs font-bold uppercase">Order Status</dt><dd class="font-semibold">{{ $order->status_label }}</dd></div>
                    <div><dt class="text-gray-400 text-xs font-bold uppercase">Buyer</dt><dd class="font-semibold">{{ $order->buyer?->name }} &middot; {{ $order->buyer?->phone ?? $order->buyer_phone }}</dd></div>
                    <div><dt class="text-gray-400 text-xs font-bold uppercase">Seller</dt><dd class="font-semibold">{{ $order->seller?->name }} &middot; {{ $order->seller?->phone }}</dd></div>
                    <div><dt class="text-gray-400 text-xs font-bold uppercase">Total Amount</dt><dd class="font-semibold">Rs. {{ number_format($order->total_amount, 2) }}</dd></div>
                    <div><dt class="text-gray-400 text-xs font-bold uppercase">Paid Online</dt><dd class="font-semibold">Rs. {{ number_format($order->paidOnline(), 2) }}</dd></div>
                </dl>
            </div>

            {{-- Complaint / Verdict --}}
            <div class="bg-white rounded-3xl border border-amber-200 shadow-sm p-6">
                <h3 class="font-black text-gray-800 mb-2">Buyer Complaint</h3>
                <p class="text-sm text-gray-600 mb-1">
                    Reason: <span class="font-semibold">{{ $order->cancellation_reason_category?->label() }}</span>
                </p>
                @if($order->cancellation_note)
                    <p class="text-sm text-gray-600 mb-3">Buyer's note: {{ $order->cancellation_note }}</p>
                @endif

                @if($order->complaint_status === \App\Enums\OrderComplaintStatus::UNDER_REVIEW)
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-bold text-amber-800 uppercase mb-2">Physical Inspection Verdict</p>
                        <p class="text-xs text-gray-600 mb-3">Record the outcome after physically inspecting the item.</p>
                        <x-textarea wire:model="verdictNote" placeholder="Inspection notes (optional)" rows="2" />
                        <div class="flex flex-wrap gap-3 mt-3">
                            <button type="button" wire:click="confirmRecordVerdict(true)" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700">
                                Confirmed Damaged
                            </button>
                            <button type="button" wire:click="confirmRecordVerdict(false)" class="rounded-xl bg-gray-700 px-4 py-2 text-xs font-bold text-white hover:bg-gray-800">
                                Not Damaged
                            </button>
                        </div>
                    </div>
                @else
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $order->complaint_status === \App\Enums\OrderComplaintStatus::CONFIRMED_DAMAGED ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-700' }}">
                        {{ $order->complaint_status->label() }}
                    </span>
                    @if($order->complaint_resolution_note)
                        <p class="text-xs text-gray-500 mt-2">Admin note: {{ $order->complaint_resolution_note }}</p>
                    @endif
                @endif

                {{-- Complaint Chat with Buyer --}}
                <div class="mt-4 rounded-2xl border border-amber-200 bg-white p-4" wire:poll.5s="pollComplaintMessages">
                    <h4 class="text-xs font-bold text-gray-800 uppercase mb-2">Messages with Buyer</h4>

                    <div class="max-h-64 overflow-y-auto space-y-2 pr-1">
                        @forelse($order->complaintMessages as $complaintMsg)
                            <div class="rounded-xl p-3 text-xs {{ $complaintMsg->sender_role === \App\Enums\ComplaintMessageSender::ADMIN ? 'bg-amber-50 border border-amber-100' : 'bg-gray-100 ml-6' }}">
                                <p class="font-bold text-[10px] uppercase tracking-wider {{ $complaintMsg->sender_role === \App\Enums\ComplaintMessageSender::ADMIN ? 'text-amber-700' : 'text-gray-500' }}">
                                    {{ $complaintMsg->sender_role->label() }} &middot; {{ $complaintMsg->created_at->format('M d, h:i A') }}
                                </p>
                                <p class="mt-1 text-gray-800">{{ $complaintMsg->body }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">No messages yet.</p>
                        @endforelse
                    </div>

                    <div class="mt-3 flex gap-2">
                        <input type="text" wire:model="complaintMessage" wire:keydown.enter="sendComplaintMessage" placeholder="Message the buyer about this complaint..." class="flex-1 rounded-xl border border-gray-200 px-3 py-2 text-xs" />
                        <button type="button" wire:click="sendComplaintMessage" class="rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700">
                            Send
                        </button>
                    </div>
                    @error('complaintMessage') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Damage Penalty --}}
            @if($order->damagePenalty)
                @php($penalty = $order->damagePenalty)
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-black text-gray-800 mb-3">Damage Penalty</h3>
                    <dl class="grid grid-cols-1 gap-4 text-sm mb-4">
                        <div><dt class="text-gray-400 text-xs font-bold uppercase">Amount</dt><dd class="font-semibold">Rs. {{ number_format($penalty->amount, 2) }}</dd></div>
                        <div><dt class="text-gray-400 text-xs font-bold uppercase">Status</dt><dd class="font-semibold">{{ $penalty->status->label() }}</dd></div>
                        <div><dt class="text-gray-400 text-xs font-bold uppercase">Due</dt><dd class="font-semibold">{{ $penalty->due_at->format('M d, Y @ h:i A') }}</dd></div>
                        <div><dt class="text-gray-400 text-xs font-bold uppercase">Restored</dt><dd class="font-semibold">{{ $penalty->restored_at ? $penalty->restored_at->format('M d, Y') : '—' }}</dd></div>
                    </dl>

                    @if($penalty->legal_action_flagged)
                        <div class="rounded-xl bg-rose-50 border border-rose-200 p-3 text-xs font-bold text-rose-700 mb-3">
                            Legal action required — the seller did not pay within 7 days and their account was deactivated.
                        </div>
                    @endif

                    @if($penalty->seller?->is_permanently_banned)
                        <div class="rounded-xl bg-gray-900 text-white p-3 text-xs font-bold mb-3">
                            This seller is permanently banned ({{ str($penalty->seller->permanent_ban_reason)->replace('_', ' ')->title() }}) — not reversible here.
                        </div>
                    @elseif($penalty->canBeRestored())
                        <button type="button" wire:click="confirmRestoreSellerAccess" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                            Restore Seller Access
                        </button>
                    @endif

                    @if($penalty->strikes->isNotEmpty())
                        <p class="text-xs text-gray-500 mt-3">Seller's damage strikes: {{ $penalty->strikes->count() }} / 3</p>
                    @endif
                </div>
            @endif

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-black text-gray-800 mb-3">Handover Details</h3>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-gray-400 text-xs font-bold uppercase">Meetup Location</dt><dd class="font-semibold">{{ $order->meetup_location ?: 'Not set' }}</dd></div>
                    <div><dt class="text-gray-400 text-xs font-bold uppercase">Meetup Time</dt><dd class="font-semibold">{{ $order->meetup_time?->format('M d, Y @ h:i A') ?? 'Not scheduled' }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    <x-confirm-modal wireModel="showVerdictModal"
        title="{{ $confirmingVerdict ? 'Confirm Damaged?' : 'Confirm Not Damaged?' }}"
        confirmClick="runConfirmedVerdict"
        :confirmLabel="$confirmingVerdict ? 'Confirmed Damaged' : 'Not Damaged'"
        :confirmClass="$confirmingVerdict ? 'btn-error' : 'btn-neutral'">
        @if($confirmingVerdict)
            Confirm this item is damaged? This refunds the buyer, revokes the seller's access, and issues a penalty.
        @else
            Confirm this item is NOT damaged? The complaint is rejected and the deposit is forfeited.
        @endif
    </x-confirm-modal>

    <x-confirm-modal wireModel="showRestoreAccessModal" title="Restore Seller Access?" confirmClick="runConfirmedRestoreAccess" confirmLabel="Restore Access" confirmClass="btn-success">
        Restore this seller's access to exactly what they had before?
    </x-confirm-modal>
</div>
