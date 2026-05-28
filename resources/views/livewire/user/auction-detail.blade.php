<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {{-- Left: Product Gallery & Info --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- Gallery --}}
            <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
                <div x-data="{ selectedImage: '{{ $auction->product->image ? Storage::url($auction->product->image) : '' }}' }" class="p-4 space-y-4">
                    <div class="aspect-video rounded-2xl bg-gray-50 flex items-center justify-center overflow-hidden border border-gray-100">
                        <template x-if="selectedImage">
                            <img :src="selectedImage" class="w-full h-full object-contain" />
                        </template>
                        <template x-if="!selectedImage">
                            <x-icon name="o-photo" class="w-16 h-16 text-gray-200" />
                        </template>
                    </div>

                    @if($auction->product->images->count() > 1)
                        <div class="flex flex-wrap gap-3">
                            @foreach($auction->product->images as $image)
                                <button @click="selectedImage = '{{ Storage::url($image->path) }}'" 
                                    class="w-20 h-20 rounded-xl border-2 overflow-hidden transition-all"
                                    :class="selectedImage === '{{ Storage::url($image->path) }}' ? 'border-primary ring-2 ring-primary/10' : 'border-gray-100 hover:border-gray-300'">
                                    <img src="{{ Storage::url($image->path) }}" class="w-full h-full object-cover" />
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Description & Specs --}}
            <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm space-y-8">
                <div>
                    <h2 class="text-2xl font-black text-gray-900 mb-4">Product Description</h2>
                    <p class="text-gray-600 leading-relaxed whitespace-pre-line">{{ $auction->product->description }}</p>
                </div>

                @if($auction->product->specifications)
                    <div class="pt-8 border-t border-gray-50">
                        <h2 class="text-xl font-black text-gray-900 mb-4">Specifications</h2>
                        <div class="bg-gray-50 rounded-2xl p-6 text-gray-700 whitespace-pre-line text-sm leading-relaxed">
                            {{ $auction->product->specifications }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: Bidding Panel --}}
        <div class="lg:col-span-4 space-y-6">
            
            {{-- Countdown Card --}}
            <div x-data="auctionTimer('{{ $auction->start_time }}', '{{ $auction->end_time }}')" class="bg-[#1F6F5F] rounded-3xl p-6 text-white shadow-xl shadow-emerald-900/10">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase tracking-widest text-white/60" x-text="isUpcoming ? 'Auction Starts In' : 'Auction Ends In'"></span>
                    <div class="flex items-center gap-1.5 bg-white/10 px-2 py-0.5 rounded-full border border-white/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-[9px] font-black uppercase tracking-widest" x-text="isUpcoming ? 'Upcoming' : 'Live'"></span>
                    </div>
                </div>
                <p class="text-3xl font-black tracking-tighter" x-text="displayText"></p>
            </div>

            {{-- Bid Card --}}
            <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm">
                <div class="mb-6">
                    <h1 class="text-2xl font-black text-gray-900 leading-tight mb-2">{{ $auction->product->name }}</h1>
                    <x-badge :value="$auction->product->category->name ?? 'Uncategorized'" class="badge-ghost font-black text-[10px] uppercase tracking-wider" />
                </div>

                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Current Bid</p>
                        <p class="text-xl font-black text-gray-900">Rs. {{ number_format($auction->current_price) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Bids</p>
                        <p class="text-xl font-black text-gray-900">{{ $auction->total_bids }}</p>
                    </div>
                </div>

                @if($auction->isLive())
                    <form wire:submit="placeBid" class="space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Your Bid Amount</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-gray-400">Rs.</span>
                                <input type="number" wire:model="bidAmount" class="w-full bg-gray-50 border-none rounded-2xl py-4 pl-12 pr-4 font-black text-lg focus:ring-2 focus:ring-primary/20" />
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2">Minimum bid: <span class="font-black text-gray-900">Rs. {{ number_format($auction->getMinNextBid()) }}</span></p>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" wire:click="setQuickBid(100)" class="bg-gray-50 hover:bg-gray-100 py-2 rounded-xl text-xs font-black transition-colors">+100</button>
                            <button type="button" wire:click="setQuickBid(500)" class="bg-gray-50 hover:bg-gray-100 py-2 rounded-xl text-xs font-black transition-colors">+500</button>
                            <button type="button" wire:click="setQuickBid(1000)" class="bg-gray-50 hover:bg-gray-100 py-2 rounded-xl text-xs font-black transition-colors">+1k</button>
                        </div>

                        <x-button type="submit" label="Place Your Bid" class="btn-primary w-full py-4 rounded-2xl font-black uppercase text-sm tracking-widest shadow-lg shadow-primary/20" spinner="placeBid" />
                    </form>
                @else
                    <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-center">
                        <p class="text-amber-800 text-xs font-bold">
                            @if($auction->isUpcoming())
                                Bidding will open once the auction starts.
                            @else
                                This auction has ended.
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            {{-- Bid History --}}
            <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="font-black text-gray-900 uppercase tracking-tighter text-sm">Bid History</h3>
                    <x-badge :value="$auction->total_bids" class="badge-neutral text-[10px]" />
                </div>
                <div class="max-h-80 overflow-y-auto">
                    @forelse($auction->bids as $bid)
                        <div class="px-6 py-4 flex items-center justify-between border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[10px] font-black">
                                    {{ substr($bid->bidder->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-xs font-black text-gray-900">{{ $bid->bidder->name }}</p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ \Carbon\Carbon::parse($bid->created_at)->diffForHumans() }}</p>
                                </div>
                            </div>
                            <p class="font-black text-gray-900 text-sm">Rs. {{ number_format($bid->bid_amount) }}</p>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <p class="text-gray-400 text-xs font-bold">No bids yet. Be the first!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        function auctionTimer(startTimeStr, endTimeStr) {
            return {
                startTime: new Date(startTimeStr).getTime(),
                endTime: new Date(endTimeStr).getTime(),
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
                        this.displayText = this.isUpcoming ? 'Starting...' : 'Ended';
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
