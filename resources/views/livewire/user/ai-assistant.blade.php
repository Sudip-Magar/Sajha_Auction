<div>
    @if($mode === 'drawer')
        <div
            x-data="{
                open: false,
                widgetHidden: false,
                init() {
                    try { this.widgetHidden = sessionStorage.getItem('ai-widget-dismissed') === '1'; } catch (e) {}
                },
                openPanel() {
                    this.open = true;
                    if (! $wire.opened) { $wire.set('opened', true); }
                },
                dismissWidget() {
                    this.widgetHidden = true;
                    try { sessionStorage.setItem('ai-widget-dismissed', '1'); } catch (e) {}
                },
            }"
            @open-ai-assistant.window="openPanel()"
            @keydown.escape.window="open = false"
        >
            {{-- Floating AI icon --}}
            <div x-show="! widgetHidden && ! open" x-cloak class="group fixed bottom-4 left-4 z-40">
                <button type="button" @click="openPanel()" aria-label="Ask our AI assistant"
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-linear-to-br from-[#1F6F5F] to-[#2FA084] text-white shadow-xl transition hover:scale-105">
                    <x-icon name="o-sparkles" class="h-6 w-6" />
                </button>

                <span class="pointer-events-none absolute left-full top-1/2 ml-3 -translate-y-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-gray-100 dark:text-gray-900" role="tooltip">
                    Need help? Ask AI
                </span>

                <button type="button" @click="dismissWidget()" aria-label="Hide AI assistant icon"
                        class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-gray-900 text-white shadow transition hover:bg-gray-700">
                    <x-icon name="o-x-mark" class="h-3 w-3" />
                </button>
            </div>

            {{-- Slide-over drawer --}}
            <div x-show="open" x-cloak x-transition:leave="transition duration-150" class="pointer-events-none fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Sajha Auction assistant">
                <div x-show="open" x-transition.opacity @click="open = false" class="pointer-events-auto absolute inset-0 bg-gray-950/40 lg:hidden"></div>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="pointer-events-auto absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl dark:bg-[#181A1F] lg:border-l lg:border-gray-200 lg:dark:border-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-[#1F6F5F] dark:bg-emerald-950/40 dark:text-[#7CE0C5]">
                                <x-icon name="o-sparkles" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-black text-gray-900 dark:text-white">Sajha Auction Guide</p>
                                <p class="text-[11px] text-gray-500">Ask how auctions and orders work</p>
                            </div>
                        </div>
                        <button type="button" @click="open = false" aria-label="Close assistant"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 dark:hover:bg-gray-800">
                            <x-icon name="o-x-mark" class="h-5 w-5" />
                        </button>
                    </div>

                    @include('livewire.user.partials.ai-assistant-chat')
                </div>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
            <div class="mb-5">
                <h1 class="flex items-center gap-3 text-2xl font-black text-gray-900 dark:text-white">
                    <x-icon name="o-sparkles" class="h-7 w-7 text-[#1F6F5F]" />
                    How does this work?
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Ask the Sajha Auction Guide about bidding, second-hand orders, meetups and payments.
                </p>
            </div>

            <div class="flex h-[70vh] flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-[#181A1F]">
                @include('livewire.user.partials.ai-assistant-chat')
            </div>
        </div>
    @endif
</div>
