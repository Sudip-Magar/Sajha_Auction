<div class="marketplace-ui min-h-screen bg-[#F7F8FA] text-gray-950 dark:bg-gray-900 dark:text-gray-100">
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#1F6F5F]/10 text-[#1F6F5F] dark:bg-[#2FA084]/15 dark:text-[#7CE0C5]">
                <x-icon name="{{ $page['icon'] }}" class="h-7 w-7" />
            </div>
            <h1 class="text-3xl sm:text-4xl font-black tracking-tight">{{ $page['title'] }}</h1>
            <p class="mt-3 text-gray-500 dark:text-gray-400">{{ $page['subtitle'] }}</p>
        </div>

        <div class="space-y-4">
            @foreach($page['sections'] as $section)
                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-[#181A1F]">
                    <h2 class="font-black text-gray-900 dark:text-gray-100">{{ $section['heading'] }}</h2>
                    <ul class="mt-3 space-y-2.5">
                        @foreach($section['points'] as $point)
                            <li class="flex items-start gap-2.5 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                <x-icon name="o-check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-[#2FA084]" />
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-6 text-center dark:border-gray-800 dark:bg-[#181A1F]">
            <p class="font-bold text-gray-700 dark:text-gray-200">Still have questions?</p>
            <div class="mt-3 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('faqs') }}" wire:navigate class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-sky-50 hover:text-[#0C8FE8] dark:bg-gray-800 dark:text-gray-200">Visit FAQ</a>
                <a href="mailto:{{ config('mail.from.address') }}" class="rounded-md bg-[#1F6F5F] px-4 py-2 text-sm font-bold text-white hover:bg-[#18594c]">Contact Us</a>
            </div>
        </div>
    </div>
</div>
