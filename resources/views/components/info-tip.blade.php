@props(['text'])

<span x-data="{ open: false }" class="relative inline-flex" @mouseenter="open = true" @mouseleave="open = false" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button" @click="open = true" aria-label="More information" :aria-expanded="open"
            class="flex h-4 w-4 items-center justify-center rounded-full border border-gray-400 text-[10px] font-black leading-none text-gray-500 transition hover:border-[#1F6F5F] hover:text-[#1F6F5F] dark:border-gray-500 dark:text-gray-400">
        ?
    </button>
    <span x-show="open" x-cloak x-transition.opacity role="tooltip"
          class="absolute right-0 top-full z-30 mt-2 w-64 rounded-xl bg-gray-900 px-3 py-2 text-left text-[11px] font-medium leading-relaxed text-white shadow-xl dark:bg-gray-100 dark:text-gray-900">
        {{ $text }}
    </span>
</span>
