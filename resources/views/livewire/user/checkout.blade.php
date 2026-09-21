<div class="min-h-screen bg-gray-50/50 py-10 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="border-b border-gray-200 pb-5 dark:border-gray-800">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                <x-icon name="o-credit-card" class="w-8 h-8 text-[#1F6F5F]" />
                Checkout & Handover Details
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Confirm your meetup place and time, and pay in cash when you meet the seller
            </p>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-12">
            {{-- Form Column --}}
            <div class="lg:col-span-7 space-y-6">

                {{-- Handover Method --}}
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-[#181A1F]">
                    <h2 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                        <x-icon name="o-map-pin" class="w-5 h-5 text-[#1F6F5F]" />
                        Handover Method
                    </h2>

                    <div class="flex items-center gap-3 rounded-2xl border-2 border-[#1F6F5F] bg-emerald-50/40 p-4 dark:bg-emerald-950/20">
                        <x-icon name="o-map-pin" class="w-7 h-7 shrink-0 text-[#1F6F5F]" />
                        <div>
                            <span class="block font-bold text-sm text-gray-900 dark:text-white">In-Person Meetup</span>
                            <span class="text-[11px] text-gray-500">Meet seller &amp; inspect product physically</span>
                        </div>
                    </div>

                    <div class="mt-6 p-4 rounded-2xl bg-emerald-50/50 border border-emerald-100 dark:bg-emerald-950/20 dark:border-emerald-900 space-y-4">
                        <h3 class="font-bold text-sm text-emerald-900 dark:text-emerald-300">
                            Meetup Location & Schedule
                        </h3>

                        <x-input label="Agreed Meetup Location *" wire:model="meetup_location" placeholder="e.g. Koteshwor Chowk / New Road Complex" icon="o-map-pin" />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="meetup_date_np" class="fieldset-legend mb-0.5">Preferred Meetup Date (B.S.) *</label>
                                <input
                                    type="text"
                                    id="meetup_date_np"
                                    wire:model="meetup_date_np"
                                    data-nepali-date="meetup-date"
                                    class="input w-full"
                                    placeholder="YYYY-MM-DD"
                                    autocomplete="off"
                                />
                                <input type="hidden" wire:model="meetup_date_en" data-english-date="meetup-date">
                                @error('meetup_date_np') <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @elseif($errors->has('meetup_date_en')) <span class="text-red-500 text-xs mt-1">{{ $errors->first('meetup_date_en') }}</span>
                                @enderror
                            </div>

                            <div>
                                <x-input label="Preferred Meetup Time *" wire:model="meetup_time_of_day" type="time" icon="o-clock" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contact & Notes --}}
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-[#181A1F] space-y-4">
                    <h2 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                        <x-icon name="o-phone" class="w-5 h-5 text-[#1F6F5F]" />
                        Buyer Contact & Instructions
                    </h2>

                    <x-input label="Buyer Contact Phone Number *" wire:model="buyer_phone" placeholder="e.g. 98XXXXXXXX" icon="o-phone" />

                    <x-textarea label="Message / Special Notes for Seller" wire:model="notes" rows="2" placeholder="e.g. I would like to inspect the battery health before handing over payment." />
                </div>

                {{-- Payment Method --}}
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-[#181A1F]">
                    <h2 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                        <x-icon name="o-banknotes" class="w-5 h-5 text-[#1F6F5F]" />
                        Payment Option
                    </h2>

                    <div class="flex items-center gap-3 rounded-xl border border-[#1F6F5F] bg-emerald-50/50 p-3.5 font-bold dark:bg-emerald-950/20">
                        <x-icon name="o-check-circle" class="w-5 h-5 shrink-0 text-[#1F6F5F]" />
                        <span class="text-sm font-semibold">Cash on Meetup / Handover</span>
                    </div>
                    <p class="mt-3 text-[11px] text-gray-500">Pay the seller in cash when you meet and inspect the item.</p>
                </div>
            </div>

            {{-- Summary Sidebar --}}
            <div class="lg:col-span-5">
                <div class="sticky top-24 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-[#181A1F]">
                    <h2 class="text-xl font-black text-gray-900 dark:text-white border-b border-gray-100 pb-4 dark:border-gray-800">
                        Items in this Order
                    </h2>

                    <div class="mt-4 space-y-3 max-h-80 overflow-y-auto pr-1">
                        @foreach($checkoutItems as $item)
                            @php
                                $prod = $item['product'];
                                $img = $prod?->image ? Storage::url($prod->image) : null;
                            @endphp
                            <div class="flex items-center gap-3 border-b border-gray-50 pb-3 dark:border-gray-800">
                                <div class="h-14 w-14 shrink-0 rounded-xl overflow-hidden bg-gray-100">
                                    @if($img)
                                        <img src="{{ $img }}" alt="{{ $prod->name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full items-center justify-center text-gray-400">
                                            <x-icon name="o-photo" class="h-6 w-6" />
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-sm text-gray-900 truncate dark:text-white">{{ $prod->name }}</h4>
                                    <p class="text-xs text-gray-500">Qty: {{ $item['quantity'] }} × Rs {{ number_format($item['price']) }}</p>
                                    @if($prod->meetup_location)
                                        <p class="text-[11px] text-emerald-600 truncate">Meetup: {{ $prod->meetup_location }}</p>
                                    @endif
                                </div>

                                <div class="text-right font-black text-sm text-gray-900 dark:text-white">
                                    Rs {{ number_format($item['subtotal']) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 border-t border-gray-100 pt-4 dark:border-gray-800 space-y-2">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Total Items</span>
                            <span class="font-bold">{{ $checkoutItems->sum('quantity') }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-black text-gray-900 dark:text-white">
                            <span>Total Payable</span>
                            <span class="text-[#1F6F5F] dark:text-[#7CE0C5]">Rs {{ number_format($totalAmount) }}</span>
                        </div>
                    </div>

                    <button type="button"
                            wire:click="placeOrder"
                            wire:loading.attr="disabled"
                            class="mt-6 w-full flex items-center justify-center gap-2 rounded-xl bg-linear-to-r from-[#1F6F5F] to-[#2FA084] py-4 text-base font-black text-white shadow-lg shadow-[#2FA084]/20 hover:opacity-95 transition-all">
                        <x-icon name="o-check-circle" class="w-6 h-6" />
                        <span wire:loading.remove wire:target="placeOrder">Confirm & Place Order</span>
                        <span wire:loading wire:target="placeOrder">Processing Order...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
