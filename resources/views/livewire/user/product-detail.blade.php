@php
    $mainImage = $product->image ? Storage::url($product->image) : null;
    $price = $product->isDirectSell()
        ? $product->sale_price
        : ($product->auction?->current_price ?: $product->starting_bid);
    $condition = str($product->condition)->replace('-', ' ')->title();
    $specLines = collect(preg_split('/\r\n|\r|\n/', (string) $product->specifications))->filter();
@endphp

<div class="marketplace-ui min-h-screen bg-white text-gray-950 dark:bg-gray-900 dark:text-gray-100">
    <div class="mx-auto max-w-[1280px] px-2 pb-14 pt-3 sm:px-3 lg:grid lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-5 lg:px-4 lg:pt-5">
        <main>
            @if($product->isAuction() && $product->auction)
                <div class="mb-4 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-2xl p-4 text-white shadow-lg flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/15 rounded-xl">
                            <x-icon name="o-ticket" class="w-6 h-6 text-emerald-200" />
                        </div>
                        <div>
                            <h2 class="font-black text-base text-white">This product is hosted on Live Auction!</h2>
                            <p class="text-xs text-emerald-100">Participate in real-time bidding, set proxy limits, and follow the live bid room.</p>
                        </div>
                    </div>
                    <a href="{{ route('user.auction.detail', $product->auction->id) }}" wire:navigate class="btn bg-white text-emerald-900 hover:bg-gray-100 border-none font-black rounded-xl px-5 shadow-md">
                        Enter Live Auction Room →
                    </a>
                </div>
            @endif

            <section class="lg:grid lg:grid-cols-[minmax(0,640px)_minmax(280px,1fr)] lg:gap-5">
                <div>
                    {{-- Images Gallery --}}
                    <div x-data="{ selectedImage: '{{ $mainImage }}' }" class="space-y-3">
                        <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-gray-100 dark:bg-gray-800 lg:aspect-[16/9]">
                            @if($mainImage)
                                <img :src="selectedImage" alt="{{ $product->name }}" class="h-full w-full object-cover lg:object-contain">
                            @else
                                <div class="flex h-full items-center justify-center"><x-icon name="o-photo" class="h-16 w-16 text-gray-300" /></div>
                            @endif

                            {{-- Wishlist Toggle Floating Button --}}
                            <button type="button"
                                    wire:click="toggleWishlist"
                                    wire:loading.attr="disabled"
                                    class="absolute top-3 right-3 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 shadow-md backdrop-blur-xs transition-transform active:scale-95 dark:bg-gray-900/90">
                                @if($isWishlisted)
                                    <x-icon name="s-heart" class="h-6 w-6 text-rose-500" />
                                @else
                                    <x-icon name="o-heart" class="h-6 w-6 text-gray-600 hover:text-rose-500 dark:text-gray-300" />
                                @endif
                            </button>
                        </div>
                        @if($product->images->count() > 1)
                            <div class="flex gap-2 overflow-x-auto pb-1">
                                @foreach($product->images as $image)
                                    <button type="button" @click="selectedImage = '{{ Storage::url($image->path) }}'" class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                                        <img src="{{ Storage::url($image->path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Badges & Quick Stats --}}
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-gray-500 text-xs">
                        <span class="flex items-center gap-1 font-semibold"><x-icon name="o-eye" class="w-4 h-4 text-gray-400" /> {{ number_format($product->views_count) }} views</span>

                        <div class="flex flex-wrap gap-1.5">
                            <span class="rounded-lg bg-emerald-100 px-2.5 py-1 font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                {{ $product->listing_type->label() }}
                            </span>
                            <span class="rounded-lg bg-gray-100 px-2.5 py-1 font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                Condition: {{ $condition }}
                            </span>
                            @if($product->usage_duration)
                                <span class="rounded-lg bg-amber-100 px-2.5 py-1 font-bold text-amber-900 dark:bg-amber-950 dark:text-amber-300">
                                    Used: {{ $product->usage_duration }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Meetup Location Card for Direct Sell --}}
                    @if($product->isDirectSell() && ($product->meetup_location || $product->location))
                        <div class="mt-5 rounded-2xl bg-emerald-50/70 p-4 border border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-800 space-y-2">
                            <div class="flex items-center gap-2 text-emerald-950 dark:text-emerald-300 font-extrabold text-sm">
                                <x-icon name="o-map-pin" class="w-5 h-5 text-emerald-600 shrink-0" />
                                Meetup Location / Place for Sale
                            </div>
                            <p class="text-xs font-bold text-emerald-900 dark:text-emerald-200">
                                {{ $product->meetup_location ?: $product->location }}
                            </p>
                            @if($product->meetup_instructions)
                                <p class="text-xs text-emerald-700 dark:text-emerald-400">
                                    <span class="font-semibold">Instructions:</span> {{ $product->meetup_instructions }}
                                </p>
                            @endif
                            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium pt-1">
                                Buyer will inspect and receive the item at this agreed location upon order confirmation.
                            </p>
                        </div>
                    @endif

                    <div class="mt-5 rounded-xl bg-gray-50 p-3 leading-5 text-xs text-gray-600 dark:bg-[#181A1F] dark:text-gray-300 border border-gray-100 dark:border-gray-800">
                        <strong>Safety Note:</strong> Physically inspect second-hand products at the specified meetup place before making final payment. Avoid advance wire transfers.
                    </div>

                    <section class="mt-5">
                        <h1 class="text-2xl font-black leading-tight text-gray-900 dark:text-white">{{ $product->name }}</h1>
                        <div class="mt-2 flex items-baseline gap-3">
                            <p class="text-3xl font-black text-[#1F6F5F] dark:text-[#7CE0C5]">Rs {{ number_format((float) $price) }}</p>
                            @if($product->retail_price && $product->retail_price > $price)
                                <p class="text-sm font-semibold text-gray-400 line-through">Rs {{ number_format((float) $product->retail_price) }}</p>
                            @endif
                            <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-bold uppercase text-gray-600 dark:bg-gray-800">
                                {{ $product->negotiable?->label() ?? 'Fixed' }}
                            </span>
                        </div>

                        <div class="mt-5 border-b border-gray-200 dark:border-gray-800">
                            <div class="flex items-center gap-5 font-bold text-sm">
                                <span class="border-b-2 border-[#1F6F5F] pb-2 text-[#1F6F5F] dark:border-[#7CE0C5] dark:text-[#7CE0C5]">Description</span>
                            </div>
                        </div>

                        <div class="mt-3 text-sm font-medium leading-relaxed text-gray-700 dark:text-gray-300">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </section>

                    {{-- Specifications --}}
                    @if($specLines->isNotEmpty())
                        <section class="mt-6">
                            <h2 class="text-lg font-black mb-2">Specifications</h2>
                            <div class="overflow-hidden rounded-2xl bg-gray-100 dark:bg-[#181A1F] text-xs">
                                @foreach($specLines as $line)
                                    <div class="grid grid-cols-[140px_1fr] border-b border-white/80 last:border-b-0 dark:border-gray-800">
                                        <div class="p-3 font-bold text-gray-800 dark:text-gray-200">{{ str($line)->before(':')->limit(24) }}</div>
                                        <div class="p-3 text-gray-600 dark:text-gray-400">{{ str($line)->contains(':') ? str($line)->after(':') : $line }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Product Lifecycle & Timeline --}}
                    @if($product->timelines->isNotEmpty())
                        <section class="mt-8 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-[#181A1F]">
                            <h2 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                                <x-icon name="o-clock" class="w-5 h-5 text-[#1F6F5F]" />
                                Product Lifecycle & Timeline
                            </h2>

                            <div class="relative pl-6 space-y-4 before:absolute before:left-2 before:top-1 before:bottom-1 before:w-0.5 before:bg-gray-200 dark:before:bg-gray-800">
                                @foreach($product->timelines as $timeline)
                                    <div class="relative">
                                        <div class="absolute -left-6 top-1 h-3 w-3 rounded-full border-2 border-white bg-[#1F6F5F] dark:border-[#181A1F]"></div>
                                        <div class="flex items-baseline justify-between gap-2">
                                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $timeline->title }}</p>
                                            <span class="text-[10px] text-gray-400">{{ $timeline->created_at->format('M d, Y @ h:i A') }}</span>
                                        </div>
                                        @if($timeline->description)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">{{ $timeline->description }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                {{-- Right Sidebar Actions --}}
                <aside class="mt-6 lg:mt-0">
                    <div class="sticky top-24 space-y-5">
                        <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-[#181A1F]">
                            <h2 class="font-bold text-base text-gray-900 dark:text-white">{{ $product->name }}</h2>
                            <p class="mt-2 text-2xl font-black text-[#1F6F5F] dark:text-[#7CE0C5]">Rs {{ number_format((float) $price) }}</p>

                            @if(auth()->check() && (int)$product->seller_id === (int)auth()->id())
                                <div class="mt-5 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-center dark:bg-emerald-950/40 dark:border-emerald-800">
                                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 rounded-xl flex items-center justify-center mx-auto mb-2">
                                        <x-icon name="o-user-circle" class="w-6 h-6" />
                                    </div>
                                    <p class="text-xs font-black text-emerald-950 dark:text-emerald-200">Your Product Listing</p>
                                    <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-1 leading-relaxed">This item is visible to marketplace buyers, but you cannot buy or bid on your own product.</p>
                                </div>
                            @else
                                {{-- Direct Sell Actions --}}
                                @if($product->isDirectSell())
                                    @if($product->status === 'sold' || $product->quantity <= 0)
                                        <div class="mt-4 rounded-xl bg-rose-100 p-3 text-center text-xs font-bold text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                            This item has been Sold
                                        </div>
                                    @else
                                        <div class="mt-5 space-y-3">
                                            <button type="button"
                                                    wire:click="buyNow"
                                                    wire:loading.attr="disabled"
                                                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-linear-to-r from-[#1F6F5F] to-[#2FA084] py-3 text-sm font-extrabold text-white shadow-lg shadow-[#2FA084]/20 hover:opacity-95 transition-all">
                                                <x-icon name="o-shopping-bag" class="w-4 h-4" />
                                                Order Product
                                            </button>

                                            <button type="button"
                                                    wire:click="addToCart"
                                                    wire:loading.attr="disabled"
                                                    class="w-full flex items-center justify-center gap-2 rounded-xl border border-[#1F6F5F] bg-emerald-50/50 py-3 text-sm font-bold text-[#1F6F5F] hover:bg-emerald-100 transition-all dark:bg-emerald-950/30 dark:text-[#7CE0C5]">
                                                <x-icon name="o-shopping-cart" class="w-4 h-4" />
                                                Add to Cart
                                            </button>

                                            <button type="button"
                                                    wire:click="openChat"
                                                    wire:loading.attr="disabled"
                                                    class="w-full flex items-center justify-center gap-2 rounded-xl border border-sky-200 bg-sky-50 py-3 text-sm font-bold text-sky-700 hover:bg-sky-100 transition-all dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300">
                                                <x-icon name="o-chat-bubble-left-right" class="w-4 h-4" />
                                                Chat with Seller
                                            </button>
                                        </div>
                                    @endif
                                @endif

                                {{-- Auction Action --}}
                                @if($product->auction)
                                    <div class="mt-5 rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900/50">
                                        <h3 class="font-bold text-xs uppercase tracking-wider text-gray-500 mb-3">Live Auction Status</h3>
                                        <div class="space-y-2 text-xs">
                                            <div class="flex justify-between"><span>Current Bid:</span><strong class="text-gray-900 dark:text-white">Rs {{ number_format((float) $product->auction->current_price) }}</strong></div>
                                            <div class="flex justify-between"><span>Bids Count:</span><strong class="text-gray-900 dark:text-white">{{ $product->auction->total_bids }}</strong></div>
                                        </div>
                                        <a href="{{ route('user.auction.detail', $product->auction) }}" wire:navigate class="mt-4 block rounded-xl bg-purple-600 py-3 text-center text-xs font-bold text-white shadow-md hover:bg-purple-700">
                                            Place Bid in Auction
                                        </a>
                                    </div>
                                @endif

                                {{-- Save to Wishlist Button --}}
                                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                    <button type="button"
                                            wire:click="toggleWishlist"
                                            wire:loading.attr="disabled"
                                            class="w-full flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-50 transition-all dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                        @if($isWishlisted)
                                            <x-icon name="s-heart" class="w-4 h-4 text-rose-500" />
                                            <span>Saved in Wishlist</span>
                                        @else
                                            <x-icon name="o-heart" class="w-4 h-4 text-gray-400" />
                                            <span>Add to Wishlist</span>
                                        @endif
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- Seller Info Card --}}
                        <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-[#181A1F]">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Seller Details</h3>
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-linear-to-br from-[#1F6F5F] to-[#2FA084] flex items-center justify-center text-white font-bold">
                                    {{ substr($product->user?->name ?? 'S', 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $product->user?->name ?? 'Seller' }}</p>
                                    <p class="text-xs text-gray-500">{{ $product->user?->phone ?? 'Contact available on order' }}</p>
                                </div>
                            </div>
                            @if(! auth()->check() || (int)$product->seller_id !== (int)auth()->id())
                                <button type="button"
                                        wire:click="openChat"
                                        wire:loading.attr="disabled"
                                        class="mt-4 w-full flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-50 transition-all dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                    <x-icon name="o-chat-bubble-left-right" class="w-4 h-4 text-sky-500" />
                                    Message Product Owner
                                </button>
                            @endif
                        </div>
                    </div>
                </aside>
            </section>

            {{-- Similar Products --}}
            @if($similarProducts->isNotEmpty())
                <section class="mt-12 border-t border-gray-100 pt-8 dark:border-gray-800">
                    <h2 class="text-xl font-black mb-4 text-gray-900 dark:text-white">Similar Second-Hand Listings</h2>
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        @foreach($similarProducts as $similarProduct)
                            <a href="{{ route('user.products.show', $similarProduct->slug) }}" wire:navigate class="block rounded-2xl border border-gray-200 bg-white p-3 hover:shadow-md transition-all dark:border-gray-800 dark:bg-[#181A1F]">
                                <div class="aspect-square overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                                    @if($similarProduct->image)
                                        <img src="{{ Storage::url($similarProduct->image) }}" alt="{{ $similarProduct->name }}" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <h3 class="mt-2 line-clamp-1 font-bold text-sm text-gray-900 dark:text-white">{{ $similarProduct->name }}</h3>
                                <p class="mt-1 font-black text-sm text-[#1F6F5F] dark:text-[#7CE0C5]">Rs {{ number_format((float) ($similarProduct->sale_price ?? $similarProduct->starting_bid)) }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>
    </div>

    @if(auth()->check() && (int) $product->seller_id === (int) auth()->id() && $productConversations->isNotEmpty())
        <section class="mx-auto mt-8 max-w-[1280px] px-2 sm:px-3 lg:px-4">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-[#181A1F]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-gray-900 dark:text-white">Buyer Messages</h2>
                        <p class="mt-1 text-xs font-semibold text-gray-500">Chat with buyers interested in this product.</p>
                    </div>
                    <x-icon name="o-chat-bubble-left-right" class="h-6 w-6 text-sky-500" />
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach($productConversations as $conversation)
                        <button type="button"
                                wire:click="openConversation({{ $conversation->id }})"
                                class="flex items-center gap-3 rounded-2xl border border-gray-200 p-3 text-left transition hover:border-sky-300 hover:bg-sky-50 dark:border-gray-700 dark:hover:border-sky-800 dark:hover:bg-sky-950/30">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-[#1F6F5F] to-[#2FA084] font-bold text-white">
                                {{ substr($conversation->buyer?->name ?? 'B', 0, 1) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-black text-gray-900 dark:text-white">{{ $conversation->buyer?->name ?? 'Buyer' }}</p>
                                <p class="mt-1 truncate text-xs font-semibold text-gray-500">{{ $conversation->messages->first()?->body ?? 'Conversation started' }}</p>
                            </div>
                            <x-icon name="o-chevron-right" class="h-4 w-4 shrink-0 text-gray-400" />
                        </button>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($chatOpen)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-gray-950/50 px-3 pb-3 backdrop-blur-sm sm:items-center sm:pb-0">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-[#181A1F]">
                <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-gray-800">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="h-10 w-10 shrink-0 rounded-full bg-linear-to-br from-[#1F6F5F] to-[#2FA084] flex items-center justify-center text-white font-bold">
                            {{ substr($product->user?->name ?? 'S', 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-gray-900 dark:text-white">{{ $product->user?->name ?? 'Seller' }}</p>
                            <p class="truncate text-[11px] font-semibold text-gray-500">{{ $product->name }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeChat" class="rounded-xl p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                        <x-icon name="o-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div data-product-chat-messages class="h-80 space-y-3 overflow-y-auto p-4">
                    @forelse($chatMessages as $message)
                        @php($isMine = auth()->check() && (int) $message['sender_id'] === (int) auth()->id())
                        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[82%] rounded-2xl px-4 py-2 {{ $isMine ? 'bg-[#1F6F5F] text-white' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100' }}">
                                <p class="text-sm font-semibold leading-relaxed">{{ $message['body'] }}</p>
                                <p class="mt-1 text-[10px] font-bold opacity-70">{{ $message['created_at'] }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full flex-col items-center justify-center text-center">
                            <x-icon name="o-chat-bubble-left-right" class="h-10 w-10 text-gray-300" />
                            <p class="mt-2 text-sm font-bold text-gray-500">Start a conversation with the product owner.</p>
                        </div>
                    @endforelse
                </div>

                <form wire:submit="sendChatMessage" class="w-full border-t border-gray-100 p-4 dark:border-gray-800">
                    <div class="flex items-center gap-2 w-full">
                        <div class="flex-1 w-full">
                            <x-input wire:model="chatMessage" placeholder="Type your message..." class="w-full text-xs" />
                        </div>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#1F6F5F] text-white hover:bg-[#2FA084] disabled:opacity-60 transition cursor-pointer">
                            <x-icon name="o-paper-airplane" class="h-4 w-4" />
                        </button>
                    </div>
                    @error('chatMessage')
                        <p class="mt-1.5 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </form>
            </div>
        </div>
    @endif

    @auth
        @script
            <script>
                const scrollChatToBottom = () => {
                    const chatBox = document.querySelector('[data-product-chat-messages]');
                    if (chatBox) {
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                };

                let activeSubscribedId = null;

                const handleIncomingMessage = (event) => {
                    const convId = Number(event?.message?.conversation_id ?? event?.conversation_id);
                    if (typeof $wire.refreshChatFromBroadcast === 'function') {
                        $wire.refreshChatFromBroadcast(convId);
                    }
                };

                const subscribeToConversation = (conversationId) => {
                    const targetId = Number(conversationId);
                    if (!targetId) {
                        return;
                    }

                    if (!window.Echo) {
                        setTimeout(() => subscribeToConversation(targetId), 300);
                        return;
                    }

                    if (activeSubscribedId === targetId) {
                        scrollChatToBottom();
                        return;
                    }

                    if (activeSubscribedId) {
                        window.Echo.leave(`conversations.${activeSubscribedId}`);
                    }

                    activeSubscribedId = targetId;

                    window.Echo.private(`conversations.${targetId}`)
                        .listen('.chat.message.sent', handleIncomingMessage)
                        .listen('ChatMessageSent', handleIncomingMessage)
                        .error((error) => {
                            console.error('Product chat channel subscription error:', error);
                        });

                    scrollChatToBottom();
                };

                const userId = '{{ auth()->id() }}';
                if (userId) {
                    const setupUserListener = () => {
                        if (!window.Echo) {
                            setTimeout(setupUserListener, 300);
                            return;
                        }
                        window.Echo.private(`App.Models.User.${userId}`)
                            .listen('.chat.message.sent', handleIncomingMessage)
                            .listen('ChatMessageSent', handleIncomingMessage);
                    };
                    setupUserListener();
                }

                $wire.on('chatConversationChanged', (event) => {
                    const rawId = typeof event === 'object' ? (event?.conversationId ?? event?.[0]?.conversationId ?? event) : event;
                    const conversationId = Number(rawId);
                    if (conversationId) {
                        subscribeToConversation(conversationId);
                    }
                });

                $wire.on('chatMessageRendered', () => {
                    queueMicrotask(scrollChatToBottom);
                });

                const currentConversationId = Number($wire.get('conversationId'));
                if (currentConversationId) {
                    subscribeToConversation(currentConversationId);
                }

                document.addEventListener('livewire:navigated', () => {
                    if (activeSubscribedId && window.Echo) {
                        window.Echo.leave(`conversations.${activeSubscribedId}`);
                        activeSubscribedId = null;
                    }
                    queueMicrotask(scrollChatToBottom);
                }, { once: true });

                queueMicrotask(scrollChatToBottom);
            </script>
        @endscript
    @endauth
</div>
