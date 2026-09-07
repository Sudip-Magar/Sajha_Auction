<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    {{-- Top Header / Breadcrumb --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-gray-500 mb-1">
                <a href="{{ route('home') }}" wire:navigate class="hover:text-[#0C8FE8]">Home</a>
                <span>/</span>
                <span class="text-gray-900 dark:text-gray-200">Live Auction</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-gray-100 flex items-center gap-3">
                <span>{{ $auction->product->name }}</span>
                @if($auction->isLive())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs font-black border border-emerald-500/20 animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        LIVE AUCTION ROOM
                    </span>
                @elseif($auction->isUpcoming())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-black border border-amber-500/20">
                        <x-icon name="o-clock" class="w-4 h-4" />
                        UPCOMING AUCTION
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-500/10 text-gray-600 dark:text-gray-400 text-xs font-black border border-gray-500/20">
                        <x-icon name="o-flag" class="w-4 h-4" />
                        AUCTION ENDED
                    </span>
                @endif
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('user.products.show', $auction->product->slug) }}" wire:navigate class="btn btn-outline btn-sm rounded-xl font-bold gap-2">
                <x-icon name="o-information-circle" class="w-4 h-4" />
                Product Details
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {{-- Left Column: Product Gallery, Info & Bayes-Nash Advisor --}}
        <div class="lg:col-span-7 space-y-6">
            
            {{-- Product Gallery --}}
            <div class="bg-white dark:bg-[#181A1F] rounded-3xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
                <div x-data="{ selectedImage: '{{ $auction->product->image ? Storage::url($auction->product->image) : '' }}' }" class="p-4 space-y-4">
                    <div class="aspect-4/3 sm:aspect-16/10 rounded-2xl bg-gray-50 dark:bg-gray-900 flex items-center justify-center overflow-hidden border border-gray-100 dark:border-gray-800">
                        <template x-if="selectedImage">
                            <img :src="selectedImage" class="w-full h-full object-contain" alt="{{ $auction->product->name }}" />
                        </template>
                        <template x-if="!selectedImage">
                            <x-icon name="o-photo" class="w-16 h-16 text-gray-300 dark:text-gray-700" />
                        </template>
                    </div>

                    @if($auction->product->images->count() > 1)
                        <div class="flex flex-wrap gap-3">
                            @foreach($auction->product->images as $image)
                                <button type="button" @click="selectedImage = '{{ Storage::url($image->path) }}'"
                                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl border-2 overflow-hidden transition-all"
                                    :class="selectedImage === '{{ Storage::url($image->path) }}' ? 'border-[#0C8FE8] ring-2 ring-[#0C8FE8]/20' : 'border-gray-200 dark:border-gray-800 hover:border-gray-400'">
                                    <img src="{{ Storage::url($image->path) }}" class="w-full h-full object-cover" />
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Suggested bid guide --}}
            @if($auction->isLive())
                <div class="bg-gradient-to-br from-indigo-900 via-slate-900 to-sky-950 rounded-3xl p-6 text-white shadow-xl border border-indigo-700/30">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 bg-indigo-500/20 rounded-2xl border border-indigo-400/30">
                            <x-icon name="o-calculator" class="w-6 h-6 text-indigo-300" />
                        </div>
                        <div class="flex items-center gap-2">
                            <div>
                                <h3 class="font-black text-lg text-white">Suggested bid guide</h3>
                                <p class="text-xs text-indigo-200">An optional estimate to help you choose a bid.</p>
                            </div>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @click="open = !open" @click.outside="open = false" class="flex h-5 w-5 items-center justify-center rounded-full border border-indigo-300 text-xs font-black text-indigo-100 hover:bg-indigo-500/30" aria-label="How the suggested bid works">?</button>
                                <div x-cloak x-show="open" x-transition class="absolute left-0 top-7 z-20 w-72 rounded-xl border border-indigo-400/40 bg-slate-950 p-3 text-xs leading-relaxed text-indigo-100 shadow-xl">
                                    Enter the highest amount the item is worth to you. The guide suggests a cautious bid using the number of active bidders. It is only a suggestion—you stay in control of the final amount.
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-slate-300 leading-relaxed mb-4">
                        Tell us the most you would personally pay. We use the current number of bidders to suggest a competitive amount that may be lower than your maximum.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                        <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-indigo-200 mb-1">What the item is worth to you</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-indigo-300 text-sm">Rs.</span>
                                <input
                                    type="number"
                                    wire:model.live.debounce.300ms="privateValuation"
                                    placeholder="e.g. 50000"
                                    class="w-full bg-indigo-950/60 border border-indigo-500/30 rounded-xl py-2.5 pl-10 pr-3 text-sm text-white font-bold placeholder-indigo-400 focus:outline-none focus:border-indigo-400"
                                />
                            </div>
                        </div>

                        <div>
                            @if($recommendedBid)
                                <div class="bg-indigo-500/20 border border-indigo-400/40 rounded-xl p-2.5 flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-bold text-indigo-300 uppercase">Suggested bid</p>
                                        <p class="text-lg font-black text-emerald-300">Rs. {{ number_format($recommendedBid, 2) }}</p>
                                    </div>
                                    <button type="button" wire:click="applyRecommendedBid" class="btn btn-xs bg-emerald-500 hover:bg-emerald-600 text-slate-950 border-none font-black rounded-lg">
                                        Use Bid
                                    </button>
                                </div>
                            @else
                                <div class="bg-indigo-950/40 border border-indigo-500/20 rounded-xl p-2.5 text-center">
                                    <p class="text-xs font-semibold text-indigo-300">Enter your valuation above to see calculated equilibrium bid.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Description & Specifications --}}
            <div class="bg-white dark:bg-[#181A1F] rounded-3xl border border-gray-200 dark:border-gray-800 p-6 sm:p-8 shadow-sm space-y-6">
                <div>
                    <h2 class="text-xl font-black text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                        <x-icon name="o-document-text" class="w-5 h-5 text-[#0C8FE8]" />
                        Description & Details
                    </h2>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed whitespace-pre-line text-sm sm:text-base">{{ $auction->product->description }}</p>
                </div>

                @if($auction->product->specifications)
                    <div class="pt-6 border-t border-gray-100 dark:border-gray-800">
                        <h3 class="text-lg font-black text-gray-900 dark:text-gray-100 mb-3">Specifications</h3>
                        <div class="bg-gray-50 dark:bg-gray-900/60 rounded-2xl p-4 sm:p-5 text-gray-700 dark:text-gray-300 whitespace-pre-line text-sm leading-relaxed border border-gray-100 dark:border-gray-800">
                            {{ $auction->product->specifications }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Column: Countdown, Bidding Control, History & Transparency --}}
        <div class="lg:col-span-5 space-y-6">

            {{-- Countdown Card --}}
            <div
                wire:key="auction-timer-{{ $auction->id }}-{{ $auction->effective_end_time?->timestamp }}"
                x-data="auctionTimer('{{ $auction->start_time }}', '{{ $auction->effective_end_time }}')"
                class="bg-gradient-to-r from-[#1F6F5F] to-[#0F9F6E] rounded-3xl p-6 text-white shadow-xl"
            >
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-black uppercase tracking-widest text-white/70" x-text="isUpcoming ? 'Auction Starts In' : 'Auction Time Remaining'"></span>
                    <div class="flex items-center gap-1.5 bg-white/15 px-2.5 py-1 rounded-full border border-white/20">
                        <span class="w-2 h-2 rounded-full" :class="isUpcoming ? 'bg-amber-400' : (isLive ? 'bg-emerald-400 animate-pulse' : 'bg-gray-400')"></span>
                        <span class="text-[10px] font-black uppercase tracking-widest" x-text="isUpcoming ? 'Upcoming' : (isLive ? 'Live Bidding' : 'Ended')"></span>
                    </div>
                </div>
                <p class="text-3xl sm:text-4xl font-black tracking-tighter" x-text="displayText"></p>
                <div class="mt-3 flex items-center justify-between text-xs text-white/80 font-bold border-t border-white/15 pt-3">
                    <span>Start: {{ $auction->start_time ? $auction->start_time->format('M d, Y h:i A') : 'N/A' }}</span>
                    <span>
                        End: {{ $auction->end_time ? $auction->end_time->format('M d, Y h:i A') : 'N/A' }}
                        @if($auction->extended_end_time)
                            <span class="text-amber-300" title="Extended due to a late bid">(extended to {{ $auction->extended_end_time->format('h:i:s A') }})</span>
                        @endif
                    </span>
                </div>
            </div>

            {{-- Auction result (if ended) --}}
            @if($auction->isEnded())
                <div class="bg-white dark:bg-[#181A1F] rounded-3xl border-2 {{ $auction->winner_id ? 'border-emerald-500' : 'border-amber-500' }} p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 {{ $auction->winner_id ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-600' }} rounded-2xl">
                            <x-icon name="{{ $auction->winner_id ? 'o-trophy' : 'o-exclamation-triangle' }}" class="w-8 h-8" />
                        </div>
                        <div class="flex items-center gap-2">
                            <div>
                                <h2 class="text-lg font-black text-gray-900 dark:text-gray-100">Auction result</h2>
                                <p class="text-xs font-bold text-gray-500">Winner and reserve-price check</p>
                            </div>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @click="open = !open" @click.outside="open = false" class="flex h-5 w-5 items-center justify-center rounded-full border border-gray-300 text-xs font-black text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800" aria-label="How the auction result is chosen">?</button>
                                <div x-cloak x-show="open" x-transition class="absolute left-0 top-7 z-20 w-72 rounded-xl border border-gray-200 bg-white p-3 text-xs leading-relaxed text-gray-700 shadow-xl dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    The highest valid bid wins if it meets the seller's reserve price. If equal highest bids are placed, the earlier one wins.
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($auction->winner_id && $auction->winner)
                        <div class="bg-emerald-50 dark:bg-emerald-950/40 rounded-2xl p-4 border border-emerald-200 dark:border-emerald-800 mb-4">
                            <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider mb-1">Winning Bidder</p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-emerald-600 text-white font-black flex items-center justify-center text-xs">
                                        {{ substr($auction->winner->name, 0, 1) }}
                                    </div>
                                    <span class="font-black text-gray-900 dark:text-gray-100">{{ $auction->winner->name }}</span>
                                </div>
                                <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">Rs. {{ number_format($auction->winning_price ?? $auction->current_price, 2) }}</span>
                            </div>
                        </div>
                    @else
                        <div class="bg-amber-50 dark:bg-amber-950/40 rounded-2xl p-4 border border-amber-200 dark:border-amber-800 mb-4">
                            <p class="text-xs font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wider">Item Unsold</p>
                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-200 mt-1">No admissible bid met or exceeded the reserve price requirement.</p>
                        </div>
                    @endif

                    @if($auction->settlement_reason)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 text-xs text-gray-600 dark:text-gray-300">
                            <span class="font-black text-gray-900 dark:text-gray-100">How this result was decided: </span>
                            {{ $auction->settlement_reason }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- Main Bidding Card --}}
            <div class="bg-white dark:bg-[#181A1F] rounded-3xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">

                {{-- Price Stats Grid --}}
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-4 border border-gray-100 dark:border-gray-800">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Current Standing Price</p>
                        <p class="text-2xl font-black text-[#0C8FE8]">Rs. {{ number_format($auction->current_price, 2) }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-4 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-1">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Minimum bid increase</p>
                            <div x-data="{ open: false }" class="relative mb-1">
                                <button type="button" @click="open = !open" @click.outside="open = false" class="flex h-4 w-4 items-center justify-center rounded-full border border-gray-300 text-[10px] font-black text-gray-500 hover:bg-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800" aria-label="What minimum bid increase means">?</button>
                                <div x-cloak x-show="open" x-transition class="absolute right-0 top-6 z-20 w-64 rounded-xl border border-gray-200 bg-white p-3 text-xs leading-relaxed text-gray-700 shadow-xl dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    Each new bid must be at least this much higher than the current price. The amount increases for higher-priced items to keep the auction moving.
                                </div>
                            </div>
                        </div>
                        <p class="text-xl font-black text-gray-900 dark:text-gray-100">+ Rs. {{ number_format($stepIncrement, 2) }}</p>
                    </div>
                </div>

                @if($auction->product->estimated_value)
                    <div class="mb-4 p-4 bg-violet-50 dark:bg-violet-950/30 rounded-2xl border border-violet-200 dark:border-violet-900/60">
                        <p class="text-[10px] font-black text-violet-500 dark:text-violet-300 uppercase tracking-widest mb-1">Estimated Value (Reference Only)</p>
                        <p class="text-lg font-black text-violet-700 dark:text-violet-300">Rs. {{ number_format($auction->product->estimated_value, 2) }}</p>
                        <p class="text-[10px] text-violet-500 dark:text-violet-400 mt-1">Based on original price, age & condition. This is not a guarantee of resale value.</p>
                    </div>
                @endif

                @if($auction->product->retail_price && $auction->current_price > $auction->product->retail_price)
                    <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl flex items-start gap-2">
                        <x-icon name="o-exclamation-triangle" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                        <p class="text-xs font-bold text-amber-800 dark:text-amber-200">Current bid is above the original purchase price of Rs. {{ number_format($auction->product->retail_price, 2) }}.</p>
                    </div>
                @endif

                @if($auction->isLive() && $canPlaceBid)
                    <form wire:submit="placeBid" class="space-y-4">

                        {{-- Mode Toggle (Manual vs Proxy Bidding) --}}
                        <div class="flex items-center justify-between bg-gray-100 dark:bg-gray-900 p-1.5 rounded-2xl">
                            <button type="button" wire:click="$set('isProxyMode', false)"
                                class="flex-1 py-2 text-xs font-black rounded-xl transition-all {{ ! $isProxyMode ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                                Manual Bid
                            </button>
                            <button type="button" wire:click="toggleProxyMode"
                                class="flex-1 py-2 text-xs font-black rounded-xl transition-all flex items-center justify-center gap-1.5 {{ $isProxyMode ? 'bg-[#0C8FE8] text-white shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                                <x-icon name="o-cpu-chip" class="w-4 h-4" />
                                Automatic bidding
                            </button>
                        </div>

                        @if(! $isProxyMode)
                            {{-- Manual Bid Input --}}
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Bid Amount</label>
                                    <span class="text-[11px] text-gray-400">Min: <strong class="text-gray-900 dark:text-gray-100">Rs. {{ number_format($auction->getMinNextBid(), 2) }}</strong></span>
                                </div>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-gray-400 text-base">Rs.</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model="bidAmount"
                                        class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl py-3.5 pl-12 pr-4 font-black text-lg text-gray-900 dark:text-white focus:outline-none focus:border-[#0C8FE8]"
                                    />
                                </div>
                            </div>

                            {{-- Quick Bid Buttons --}}
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" wire:click="setQuickBid(100)" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 py-2 rounded-xl text-xs font-black transition-colors text-gray-800 dark:text-gray-200">+ Rs. 100</button>
                                <button type="button" wire:click="setQuickBid(500)" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 py-2 rounded-xl text-xs font-black transition-colors text-gray-800 dark:text-gray-200">+ Rs. 500</button>
                                <button type="button" wire:click="setQuickBid(1000)" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 py-2 rounded-xl text-xs font-black transition-colors text-gray-800 dark:text-gray-200">+ Rs. 1,000</button>
                            </div>
                        @else
                            {{-- Automatic bidding input --}}
                            <div class="p-4 bg-sky-50 dark:bg-sky-950/40 rounded-2xl border border-sky-200 dark:border-sky-900/60 space-y-3">
                                <div class="flex items-center gap-2 text-sky-900 dark:text-sky-200 font-bold text-xs">
                                    <x-icon name="o-sparkles" class="w-4 h-4 text-[#0C8FE8]" />
                                    <span>Automatic bidding</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open" @click.outside="open = false" class="flex h-4 w-4 items-center justify-center rounded-full border border-sky-500 text-[10px] font-black text-sky-700 hover:bg-sky-100 dark:text-sky-200 dark:hover:bg-sky-900" aria-label="How automatic bidding works">?</button>
                                        <div x-cloak x-show="open" x-transition class="absolute left-0 top-6 z-20 w-72 rounded-xl border border-sky-200 bg-white p-3 text-xs leading-relaxed text-sky-900 shadow-xl dark:border-sky-800 dark:bg-gray-900 dark:text-sky-100">
                                            Set the most you are willing to pay. Your maximum stays private. If someone bids against you, the system raises your bid by only the minimum required amount, stopping at your limit.
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-sky-800 dark:text-sky-300">Set your private maximum amount. We will bid for you only when needed and never go above that limit.</p>

                                @if($currentProxyMaximum !== null)
                                    <div class="rounded-xl border border-sky-200 bg-white/70 px-3 py-2 text-xs text-sky-900 dark:border-sky-800 dark:bg-gray-900/70 dark:text-sky-200">
                                        Your current private maximum: <strong>Rs. {{ number_format($currentProxyMaximum, 2) }}</strong>
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-[11px] font-black text-sky-900 dark:text-sky-200 uppercase tracking-widest mb-1">Your private maximum</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-sky-500 text-base">Rs.</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            wire:model="maxProxyAmount"
                                            placeholder="Enter maximum willing amount"
                                            class="w-full bg-white dark:bg-gray-900 border border-sky-300 dark:border-sky-800 rounded-2xl py-3 pl-12 pr-4 font-black text-lg text-gray-900 dark:text-white focus:outline-none focus:border-[#0C8FE8]"
                                        />
                                    </div>
                                    <p class="text-[10px] text-sky-700 dark:text-sky-400 mt-1">Starting auto-bid at minimum: <strong>Rs. {{ number_format($auction->getMinNextBid(), 2) }}</strong></p>
                                </div>
                            </div>
                        @endif

                        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full py-4 rounded-2xl font-black uppercase text-sm tracking-widest shadow-lg bg-[#0C8FE8] hover:bg-[#0877C2] border-none text-white flex items-center justify-center gap-2">
                            <x-icon name="o-bolt" class="w-5 h-5" />
                            <span>{{ $isProxyMode ? 'Register Secret Proxy Bid' : 'Place Live Bid' }}</span>
                        </button>
                    </form>
                @elseif($auction->isLive())
                    <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 text-center">
                        <p class="text-amber-900 dark:text-amber-200 text-xs font-bold">Your account is not approved for live auction bidding.</p>
                        <a href="{{ route('user.join-auction') }}" wire:navigate class="mt-2 inline-block text-xs font-black text-[#0C8FE8] hover:underline">Apply for auction access</a>
                    </div>
                @else
                    <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 text-center">
                        <p class="text-amber-900 dark:text-amber-200 text-xs font-bold">
                            @if($auction->isUpcoming())
                                Live bidding will open automatically when the start time is reached.
                            @else
                                Bidding is closed for this auction listing.
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            {{-- Transparent Bid History (Latest 10 Bids) --}}
            <div class="bg-white dark:bg-[#181A1F] rounded-3xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="font-black text-gray-900 dark:text-gray-100 uppercase tracking-tight text-sm">Transparent Bid History</h3>
                        <p class="text-[10px] font-bold text-gray-500">Showing latest live bids</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 font-black text-xs text-gray-700 dark:text-gray-300">
                        {{ $auction->total_bids }} total bids
                    </span>
                </div>

                <div class="max-h-96 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($recentBids as $bid)
                        <div class="px-6 py-3.5 flex items-center justify-between hover:bg-gray-50/80 dark:hover:bg-gray-900/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#0C8FE8]/10 text-[#0C8FE8] flex items-center justify-center text-xs font-black">
                                    {{ substr($bid->bidder?->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-black text-gray-900 dark:text-gray-100">{{ $bid->bidder?->name ?? 'Anonymous Bidder' }}</p>
                                        @if($bid->is_proxy)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-sky-100 text-sky-700 dark:bg-sky-900/60 dark:text-sky-300">PROXY</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">DIRECT</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-gray-400 font-semibold">{{ $bid->created_at ? $bid->created_at->diffForHumans() : 'Just now' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-gray-900 dark:text-gray-100 text-sm">Rs. {{ number_format($bid->bid_amount, 2) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <x-icon name="o-inbox-stack" class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                            <p class="text-gray-400 text-xs font-bold">No bids placed yet. Be the first to bid!</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    {{-- Alpine Timer Component --}}
    <script>
        function auctionTimer(startTimeStr, endTimeStr) {
            return {
                startTime: startTimeStr ? new Date(startTimeStr).getTime() : 0,
                endTime: endTimeStr ? new Date(endTimeStr).getTime() : 0,
                now: new Date().getTime(),
                isUpcoming: false,
                isLive: false,
                displayText: '',
                timer: null,

                init() {
                    this.update();
                    this.timer = setInterval(() => this.update(), 1000);
                },

                update() {
                    this.now = new Date().getTime();
                    this.isUpcoming = this.now < this.startTime;
                    this.isLive = this.now >= this.startTime && this.now <= this.endTime;

                    let target = this.isUpcoming ? this.startTime : this.endTime;
                    let diff = target - this.now;

                    if (diff <= 0) {
                        this.displayText = this.isUpcoming ? 'Starting...' : 'Auction Ended';
                        if (!this.isUpcoming) clearInterval(this.timer);
                        return;
                    }

                    let days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    let hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    let mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    let secs = Math.floor((diff % (1000 * 60)) / 1000);

                    if (days > 0) {
                        this.displayText = `${days}d ${hours}h ${mins}m ${secs}s`;
                    } else {
                        this.displayText = `${hours}h ${mins}m ${secs}s`;
                    }
                }
            }
        }
    </script>
</div>
