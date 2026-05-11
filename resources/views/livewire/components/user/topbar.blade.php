<header class="sticky top-0 z-30 bg-linear-to-r from-[#2FA084] to-[#1F6F5F] backdrop-blur-md border-b border-gray-100 shadow-sm h-12 flex items-center px-8 lg:px-12 justify-between">
    <h2 class="text-xl font-bold text-gray-800">{{ $title ?? 'Dashboard' }}</h2>
    <div class="flex items-center space-x-4">
        <button class="p-2 text-gray-400 hover:text-[#2FA084] transition-colors cursor-pointer">
            <x-icon name="o-bell" class="w-6 h-6" />
            <span class="px-2 py-.5 rounded-full font-semibold flex items-center justify-between inline-block bg-red-500 text-white">
                {{ $notifications->count() }}
            </span>
        </button>
    </div>
</header>
