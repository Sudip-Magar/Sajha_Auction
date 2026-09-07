<div class="marketplace-ui min-h-screen bg-[#F7F8FA] text-gray-950 dark:bg-gray-900 dark:text-gray-100">
<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <x-header title="Live Auctions" subtitle="Bid on premium items in real-time" separator progress-indicator />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($auctions as $auction)
            <div x-data="auctionTimer('{{ $auction->start_time }}', '{{ $auction->end_time }}')" class="group bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col h-full dark:bg-[#181A1F] dark:border-gray-800 dark:shadow-none">

                {{-- Image & Badge --}}
                <div class="relative aspect-[4/3] overflow-hidden bg-gray-50 dark:bg-gray-800">
                    @if($auction->product->image)
                        <img src="{{ Storage::url($auction->product->image) }}" alt="{{ $auction->product->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <x-icon name="o-photo" class="w-12 h-12 text-gray-200" />
                        </div>
                    @endif

                    <div class="absolute top-3 left-3 flex flex-col gap-2">
                        <template x-if="isUpcoming">
                            <x-badge value="Upcoming" class="badge-warning font-black text-[10px] uppercase tracking-wider" />
                        </template>
                        <template x-if="isLive">
                            <x-badge value="Live" class="badge-success font-black text-[10px] uppercase tracking-wider animate-pulse" />
                        </template>
                        <template x-if="isEnded">
                            <x-badge value="Auction Ended" class="badge-neutral font-black text-[10px] uppercase tracking-wider" />
                        </template>
                    </div>

                    <div class="absolute bottom-3 right-3">
                        <div class="bg-black/60 backdrop-blur-md text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-white/20">
                            {{ $auction->total_bids }} Bids
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-5 flex flex-col flex-1">
                    <div class="flex-1">
                        <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-1">{{ $auction->product->category->name ?? 'Uncategorized' }}</p>
                        <h3 class="font-black text-gray-900 line-clamp-1 mb-2 dark:text-gray-100">{{ $auction->product->name }}</h3>

                        <div class="flex items-end justify-between gap-4 py-3 border-y border-gray-50 dark:border-gray-800">
                            <div>
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Current Bid</p>
                                <p class="text-lg font-black text-gray-900 dark:text-gray-100">Rs. {{ number_format($auction->current_price) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest" x-text="isUpcoming ? 'Starts In' : (isLive ? 'Ends In' : 'Auction Ended')"></p>
                                <p class="text-sm font-black text-primary" x-text="displayText"></p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <x-button label="View Details" class="btn-primary w-full rounded-2xl font-black uppercase text-xs tracking-widest"
                            link="{{ route('user.auction.detail', $auction->id) }}" />
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200 dark:bg-gray-800/40 dark:border-gray-800">
                <x-icon name="o-inbox-stack" class="w-12 h-12 text-gray-300 mx-auto mb-4 dark:text-gray-700" />
                <p class="text-gray-500 font-bold dark:text-gray-400">No active auctions found at the moment.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-10">
        {{ $auctions->links() }}
    </div>

    <script>
        function auctionTimer(startTimeStr, endTimeStr) {
            return {
                startTime: new Date(startTimeStr).getTime(),
                endTime: new Date(endTimeStr).getTime(),
                now: new Date().getTime(),
                isUpcoming: false,
                isLive: false,
                isEnded: false,
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
                    this.isEnded = this.now > this.endTime;

                    let target = this.isUpcoming ? this.startTime : this.endTime;
                    let diff = target - this.now;

                    if (this.isEnded) {
                        this.displayText = 'Auction ended';
                        clearInterval(this.timer);
                        return;
                    }

                    let days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    let hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    let mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    let secs = Math.floor((diff % (1000 * 60)) / 1000);

                    if (days > 0) {
                        this.displayText = `${days}d ${hours}h`;
                    } else {
                        this.displayText = `${hours}h ${mins}m ${secs}s`;
                    }
                }
            }
        }
    </script>
</div>
</div>
