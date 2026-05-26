<div>
    @if(!$is_auction_allowed && !$hide_notice && $is_logged_in)
        <div class="fixed bottom-4 right-4 z-50">
            <div
                class="relative w-80 overflow-hidden rounded-2xl shadow-2xl text-white bg-linear-to-r from-[#1F6F5F] to-[#2FA084] p-5">

                <!-- Close Button -->
                <button
                    wire:click="hide"
                    class="absolute top-3 right-3 flex items-center justify-center w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 transition cursor-pointer">
                    <x-icon name="o-x-mark" class="w-5 h-5"/>
                </button>

                <!-- Content -->
                <div class="flex items-start gap-4">

                    <!-- Icon -->
                    <div
                        class="flex items-center justify-center min-w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm">
                        <x-icon name="o-hand-raised" class="w-6 h-6"/>
                    </div>

                    <!-- Text -->
                    <div class="pr-6">
                        <h3 class="text-lg font-semibold">
                            Bidding Access Required
                        </h3>

                        <p class="mt-1 text-sm text-white/90 leading-relaxed">
                            Apply now to enable bidding and participate in live auctions.
                        </p>

                        <!-- Button -->
                        <a href="{{route('user.join-auction')}}"
                            class="mt-4 inline-block px-4 py-2 rounded-lg bg-white text-[#1F6F5F] font-semibold text-sm hover:bg-gray-100 transition">
                            Apply Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
