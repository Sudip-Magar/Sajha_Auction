<div class="marketplace-ui min-h-screen bg-[#F7F8FA] text-gray-950 dark:bg-gray-900 dark:text-gray-100">
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h1 class="text-3xl sm:text-4xl font-black tracking-tight">Frequently Asked Questions</h1>
            <p class="mt-3 text-gray-500 dark:text-gray-400">Answers to common questions about bidding, selling, and using Sajha Auction.</p>
        </div>

        @if($categories->isNotEmpty())
            <div class="flex flex-wrap justify-center gap-2 mb-10">
                <button
                    type="button"
                    wire:click="$set('categoryFilter', '')"
                    @class([
                        'rounded-full px-4 py-2 text-sm font-bold transition-colors',
                        'bg-[#1F6F5F] text-white' => $categoryFilter === '',
                        'bg-white text-gray-600 border border-gray-200 hover:border-[#2FA084] hover:text-[#2FA084] dark:bg-[#181A1F] dark:text-gray-300 dark:border-gray-800' => $categoryFilter !== '',
                    ])
                >
                    All
                </button>
                @foreach($categories as $category)
                    <button
                        type="button"
                        wire:click="$set('categoryFilter', {{ $category->id }})"
                        @class([
                            'rounded-full px-4 py-2 text-sm font-bold transition-colors',
                            'bg-[#1F6F5F] text-white' => (int) $categoryFilter === $category->id,
                            'bg-white text-gray-600 border border-gray-200 hover:border-[#2FA084] hover:text-[#2FA084] dark:bg-[#181A1F] dark:text-gray-300 dark:border-gray-800' => (int) $categoryFilter !== $category->id,
                        ])
                    >
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        @endif

        @if($faqs->isNotEmpty())
            <div class="space-y-3">
                @foreach($faqs as $faq)
                    <div
                        x-data="{ open: false }"
                        class="rounded-2xl border border-gray-200 bg-white overflow-hidden dark:border-gray-800 dark:bg-[#181A1F]"
                    >
                        <button
                            type="button"
                            @click="open = ! open"
                            class="w-full flex items-center justify-between gap-4 p-5 text-left"
                        >
                            <span class="font-bold text-gray-900 dark:text-gray-100">{{ $faq->question }}</span>
                            <x-icon name="o-chevron-down" class="w-5 h-5 shrink-0 text-gray-400 transition-transform duration-300" x-bind:class="open ? 'rotate-180' : ''" />
                        </button>
                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="px-5 pb-5 -mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400"
                            style="display: none;"
                        >
                            {{ $faq->answer }}
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-12 text-center dark:border-gray-800 dark:bg-[#181A1F]">
                <x-icon name="o-question-mark-circle" class="w-12 h-12 text-gray-200 mx-auto mb-4 dark:text-gray-700" />
                <p class="text-gray-500 font-semibold dark:text-gray-400">No FAQs available{{ $categoryFilter !== '' ? ' for this category' : '' }} yet.</p>
            </div>
        @endif
    </div>
</div>
