<div class="min-h-[calc(100vh-4rem)] bg-gray-50/50 py-6 text-gray-950 dark:bg-gray-900 dark:text-gray-100">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                    <x-icon name="o-chat-bubble-left-right" class="w-7 h-7 text-[#1F6F5F] dark:text-[#7CE0C5]" />
                    Messages & Conversations
                </h1>
                <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                    Chat directly with product buyers and sellers in real time.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-180 overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-[#181A1F]">
            {{-- Conversations Sidebar --}}
            <aside class="lg:col-span-4 flex flex-col border-r border-gray-100 dark:border-gray-800 h-full overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-900/50 flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-gray-500">Recent Chats</span>
                    <span class="rounded-full bg-[#1F6F5F]/10 px-2.5 py-0.5 text-[11px] font-bold text-[#1F6F5F] dark:text-[#7CE0C5]">
                        {{ $conversations->count() }} Conversations
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-800/60">
                    @forelse($conversations as $conv)
                        @php
                            $isBuyer = (int)$conv->buyer_id === (int)auth()->id();
                            $partner = $isBuyer ? $conv->seller : $conv->buyer;
                            $isSelected = (int)$conv->id === (int)$selectedConversationId;
                            $latestMsg = $conv->messages->first();
                            $hasUnread = $conv->messages()
                                ->where('sender_id', '!=', auth()->id())
                                ->whereNull('read_at')
                                ->exists();
                        @endphp

                        <button type="button"
                                wire:click="selectConversation({{ $conv->id }})"
                                @class([
                                    'w-full text-left p-4 flex items-start gap-3 transition-all cursor-pointer relative',
                                    'bg-emerald-50/60 dark:bg-emerald-950/30' => $isSelected,
                                    'hover:bg-gray-50 dark:hover:bg-gray-800/40' => !$isSelected,
                                ])>
                            @if($isSelected)
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-[#1F6F5F] dark:bg-[#7CE0C5] rounded-r-full"></div>
                            @endif

                            <div class="relative h-11 w-11 shrink-0 rounded-2xl bg-linear-to-br from-[#1F6F5F] to-[#2FA084] flex items-center justify-center font-bold text-white shadow-xs">
                                {{ substr($partner?->name ?? 'U', 0, 1) }}
                                @if($hasUnread)
                                    <span class="absolute -top-1 -right-1 h-3.5 w-3.5 rounded-full bg-rose-500 border-2 border-white dark:border-[#181A1F]"></span>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <h2 class="truncate text-xs font-black text-gray-900 dark:text-white">
                                        {{ $partner?->name ?? 'User' }}
                                    </h2>
                                    @if($latestMsg)
                                        <span class="text-[10px] font-semibold text-gray-400 shrink-0">
                                            {{ $latestMsg->created_at?->diffForHumans(short: true) }}
                                        </span>
                                    @endif
                                </div>

                                <p class="truncate text-[11px] font-bold text-[#1F6F5F] dark:text-[#7CE0C5] mt-0.5">
                                    {{ $conv->product?->name ?? 'Product' }}
                                </p>

                                <p @class([
                                    'truncate text-xs mt-1 leading-relaxed',
                                    'font-extrabold text-gray-900 dark:text-white' => $hasUnread,
                                    'font-medium text-gray-500 dark:text-gray-400' => !$hasUnread,
                                ])>
                                    {{ $latestMsg?->body ?? 'No messages yet.' }}
                                </p>
                            </div>
                        </button>
                    @empty
                        <div class="flex h-full flex-col items-center justify-center p-8 text-center">
                            <x-icon name="o-chat-bubble-left-right" class="h-12 w-12 text-gray-300 dark:text-gray-700 mx-auto" />
                            <p class="mt-3 text-sm font-bold text-gray-600 dark:text-gray-400">No conversations yet</p>
                            <p class="mt-1 text-xs text-gray-400">Message product owners on the marketplace to start chatting.</p>
                        </div>
                    @endforelse
                </div>
            </aside>

            {{-- Main Chat Window --}}
            <main class="lg:col-span-8 flex flex-col h-full overflow-hidden bg-white dark:bg-[#181A1F]">
                @if($selectedConversation)
                    @php
                        $isBuyer = (int)$selectedConversation->buyer_id === (int)auth()->id();
                        $partner = $isBuyer ? $selectedConversation->seller : $selectedConversation->buyer;
                        $product = $selectedConversation->product;
                    @endphp

                    {{-- Chat Header --}}
                    <div class="p-4 border-b border-gray-100 bg-gray-50/40 dark:border-gray-800 dark:bg-gray-900/40 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="h-10 w-10 shrink-0 rounded-2xl bg-linear-to-br from-[#1F6F5F] to-[#2FA084] flex items-center justify-center font-bold text-white">
                                {{ substr($partner?->name ?? 'U', 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-black text-gray-900 dark:text-white">
                                    {{ $partner?->name ?? 'User' }}
                                </h3>
                                <p class="truncate text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    Product: {{ $product?->name }} (Rs {{ number_format((float)($product?->sale_price ?? $product?->starting_bid)) }})
                                </p>
                            </div>
                        </div>

                        @if($product)
                            <a href="{{ route('user.products.show', $product->slug) }}"
                               wire:navigate
                               class="shrink-0 flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                <x-icon name="o-eye" class="w-4 h-4 text-[#1F6F5F]" />
                                <span>View Product</span>
                            </a>
                        @endif
                    </div>

                    {{-- Messages Scroll Area --}}
                    <div data-messages-chat-box class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50/30 dark:bg-[#121316]">
                        @forelse($chatMessages as $msg)
                            @php($isMine = (int)$msg['sender_id'] === (int)auth()->id())
                            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[78%] rounded-2xl px-4 py-2.5 shadow-2xs {{ $isMine ? 'bg-[#1F6F5F] text-white rounded-br-none' : 'bg-white text-gray-900 border border-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 rounded-bl-none' }}">
                                    <p class="text-xs font-medium leading-relaxed">{{ $msg['body'] }}</p>
                                    <p class="mt-1 text-[10px] font-bold opacity-75 text-right">{{ $msg['created_at'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="flex h-full flex-col items-center justify-center text-center">
                                <x-icon name="o-chat-bubble-left-right" class="h-10 w-10 text-gray-300 dark:text-gray-700" />
                                <p class="mt-2 text-xs font-bold text-gray-500">No messages in this conversation yet.</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Message Form --}}
                    <form wire:submit="sendChatMessage" class="w-full p-4 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-[#181A1F]">
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
                @else
                    <div class="flex h-full flex-col items-center justify-center p-8 text-center">
                        <div class="w-16 h-16 rounded-3xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-[#1F6F5F] dark:text-[#7CE0C5] mb-4">
                            <x-icon name="o-chat-bubble-left-right" class="w-8 h-8" />
                        </div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white">Select a Conversation</h3>
                        <p class="mt-1 text-xs text-gray-500 max-w-sm">Choose a chat from the sidebar to view messages or start chatting with sellers and buyers.</p>
                    </div>
                @endif
            </main>
        </div>
    </div>

    @auth
        @script
            <script>
                const scrollChatToBottom = () => {
                    const chatBox = document.querySelector('[data-messages-chat-box]');
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
                            console.error('Messages page channel subscription error:', error);
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

                const currentConversationId = Number($wire.get('selectedConversationId'));
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
