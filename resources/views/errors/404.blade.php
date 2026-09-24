<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Sajha Auction</title>

    <script>
        (function () {
            var stored = localStorage.getItem('theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = stored === 'dark' || (!stored && prefersDark);
            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-gray-950 transition-colors dark:bg-gray-900 dark:text-gray-100">

    <div class="relative flex min-h-screen flex-col overflow-hidden">
        {{-- Soft brand-colored glow, top-right and bottom-left --}}
        <div class="pointer-events-none absolute -top-32 -right-32 h-96 w-96 rounded-full bg-[#2FA084]/10 blur-3xl dark:bg-[#2FA084]/10"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-[#1F6F5F]/10 blur-3xl dark:bg-[#1F6F5F]/10"></div>

        {{-- Logo --}}
        <header class="relative z-10 px-4 pt-6 sm:px-6 sm:pt-8">
            <a href="{{ url('/') }}" class="inline-flex items-center group">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-linear-to-br from-[#1F6F5F] to-[#2FA084] shadow-lg shadow-[#2FA084]/20 transition-transform duration-300 group-hover:scale-110">
                    <x-icon name="o-bolt" class="h-6 w-6 text-white" />
                </div>
                <span class="ml-3 text-2xl font-black tracking-tight text-[#1F6F5F] dark:text-[#7CE0C5]">
                    Sajha<span class="text-gray-900 dark:text-white">Auction</span>
                </span>
            </a>
        </header>

        {{-- Main content --}}
        <main class="relative z-10 flex flex-1 items-center justify-center px-4 py-12 sm:px-6">
            <div class="mx-auto w-full max-w-2xl text-center">

                {{-- Big 404 numeral. Deliberately NOT an arbitrary text-[Nrem]
                     value or a bg-clip-text gradient: this project's global
                     `body { font-size: 12px !important }` (see app.css) wins
                     over those every time. Tailwind's own preset text-*
                     sizes survive it fine (same as the logo wordmark above),
                     so this sticks to the preset scale and a solid color. --}}
                <h1 class="text-4xl font-black leading-none text-[#1F6F5F] sm:text-3xl dark:text-[#7CE0C5]">
                    404
                </h1>

                <p class="mt-2 text-xl font-black text-gray-900 sm:text-2xl dark:text-gray-100">
                    Page Not Found
                </p>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-gray-500 sm:text-base dark:text-gray-400">
                    The page you're looking for doesn't exist, was moved, or the link might be broken.
                    Let's get you back on track.
                </p>

                {{-- Actions --}}
                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ url('/') }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-linear-to-r from-[#1F6F5F] to-[#2FA084] px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#2FA084]/20 transition-all hover:opacity-95 sm:w-auto">
                        <x-icon name="o-home" class="h-4 w-4" />
                        Back to Home
                    </a>
                    <a href="{{ url('/products') }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-[#1F6F5F] bg-emerald-50/50 px-6 py-3 text-sm font-bold text-[#1F6F5F] transition-all hover:bg-emerald-100 sm:w-auto dark:border-[#2FA084]/40 dark:bg-emerald-950/30 dark:text-[#7CE0C5] dark:hover:bg-emerald-950/50">
                        <x-icon name="o-shopping-bag" class="h-4 w-4" />
                        Browse Products
                    </a>
                    <a href="{{ url('/auction') }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition-all hover:bg-gray-50 sm:w-auto dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <x-icon name="o-ticket" class="h-4 w-4" />
                        Live Auctions
                    </a>
                </div>
            </div>
        </main>

        <footer class="relative z-10 px-4 pb-6 text-center text-xs font-semibold text-gray-400 dark:text-gray-600">
            &copy; {{ date('Y') }} Sajha Auction. All rights reserved.
        </footer>
    </div>

</body>
</html>
