<aside x-data="{ mobileMenuOpen: false }" class="relative">
    <!-- Mobile Toggle -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button @click="mobileMenuOpen = !mobileMenuOpen"
                class="p-2 bg-white rounded-xl shadow-lg border border-gray-100 text-[#1F6F5F] focus:outline-none">
            <x-icon x-show="!mobileMenuOpen" name="o-bars-3-bottom-left" class="w-6 h-6" />
            <x-icon x-show="mobileMenuOpen" name="o-x-mark" class="w-6 h-6" style="display: none;" />
        </button>
    </div>

    <!-- Backdrop for mobile -->
    <div x-show="mobileMenuOpen"
         @click="mobileMenuOpen = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 lg:hidden"
         style="display: none;"></div>

    <!-- Sidebar Content -->
    <div x-bind:class="[
            mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            $store.adminSidebar.collapsed ? 'lg:w-20' : 'lg:w-72'
         ]"
         class="fixed top-0 left-0 h-full w-72 bg-gray-900 text-gray-300 z-50 transition-all duration-300 ease-in-out border-r border-gray-800 shadow-2xl flex flex-col overflow-hidden">

        <!-- Logo Section -->
        <div class="p-6 flex items-center border-b border-gray-800/50 h-20 shrink-0">
            <div class="w-10 h-10 bg-linear-to-br from-[#1F6F5F] to-[#2FA084] rounded-xl flex items-center justify-center shadow-lg shadow-[#2FA084]/20 shrink-0">
                <x-icon name="o-bolt" class="w-6 h-6 text-white" />
            </div>
            <div x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="ml-3 overflow-hidden whitespace-nowrap">
                <h1 class="text-xl font-black text-white tracking-tight">Sajha<span class="text-[#2FA084]">Admin</span></h1>
                <p class="text-[10px] uppercase tracking-widest font-bold text-gray-500">Auction Manager</p>
            </div>
        </div>

        <!-- User Profile Quick Info -->
        <div class="p-4 shrink-0">
            <div x-bind:class="$store.adminSidebar.collapsed ? 'justify-center p-1' : 'p-3'"
                 class="bg-gray-800/50 rounded-2xl border border-gray-700/50 flex items-center group cursor-pointer hover:bg-gray-800 transition-all duration-300">
                <div x-bind:class="$store.adminSidebar.collapsed ? 'w-10 h-10' : 'w-11 h-11'"
                     class="rounded-xl overflow-hidden border-2 border-[#2FA084]/30 shrink-0 shadow-inner transition-all duration-300">
                    @if(auth()->guard('admin')->user()->avatar)
                        <img src="{{ Storage::url(auth()->guard('admin')->user()->avatar) }}" alt="Admin Avatar" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-linear-to-br from-[#1F6F5F] to-[#2FA084] flex items-center justify-center text-white font-bold text-lg">
                            {{ substr(auth()->guard('admin')->user()->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="ml-3 flex-1 min-w-0 overflow-hidden">
                    <p class="text-sm font-bold text-white truncate">{{ auth()->guard('admin')->user()->name }}</p>
                    <p class="text-[10px] text-gray-400 font-medium uppercase tracking-tighter">{{ auth()->guard('admin')->user()->role ?? 'Super Admin' }}</p>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 px-4 space-y-1.5 overflow-y-auto no-scrollbar py-2">
            <p x-show="!$store.adminSidebar.collapsed" class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-3">Main Menu</p>

            <a href="{{ route('admin.dashboard') }}" wire:navigate
               x-bind:class="$store.adminSidebar.collapsed ? 'justify-center' : 'px-4'"
               class="flex items-center space-x-3 py-3.5 rounded-xl transition-all duration-300 group {{ request()->routeIs('admin.dashboard') ? 'bg-[#1F6F5F]/10 text-[#2FA084] border-l-4 border-[#2FA084]' : 'hover:bg-gray-800 hover:text-white' }}">
                <x-icon name="o-squares-2x2" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-[#2FA084]' : 'text-gray-500 group-hover:text-[#2FA084]' }}" />
                <span x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="font-semibold text-sm whitespace-nowrap">Dashboard</span>
            </a>

             <a href="{{ route('admin.category-setup') }}" wire:navigate
               x-bind:class="$store.adminSidebar.collapsed ? 'justify-center' : 'px-4'"
               class="flex items-center space-x-3 py-3.5 rounded-xl transition-all duration-300 group {{ request()->routeIs('admin.category-setup') ? 'bg-[#1F6F5F]/10 text-[#2FA084] border-l-4 border-[#2FA084]' : 'hover:bg-gray-800 hover:text-white' }}">
                <x-icon name="o-tag" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.category-setup') ? 'text-[#2FA084]' : 'text-gray-500 group-hover:text-[#2FA084]' }}" />
                <span x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="font-semibold text-sm whitespace-nowrap">Categories</span>
            </a>

            <a href="{{ route('admin.sub-category-setup') }}" wire:navigate
               x-bind:class="$store.adminSidebar.collapsed ? 'justify-center' : 'px-4'"
               class="flex items-center space-x-3 py-3.5 rounded-xl transition-all duration-300 group {{ request()->routeIs('admin.sub-category-setup') ? 'bg-[#1F6F5F]/10 text-[#2FA084] border-l-4 border-[#2FA084]' : 'hover:bg-gray-800 hover:text-white' }}">
                <x-icon name="o-rectangle-stack" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.sub-category-setup') ? 'text-[#2FA084]' : 'text-gray-500 group-hover:text-[#2FA084]' }}" />
                <span x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="font-semibold text-sm whitespace-nowrap">Sub-Categories</span>
            </a>

            <a href="{{ route('admin.users') }}" wire:navigate
               x-bind:class="$store.adminSidebar.collapsed ? 'justify-center' : 'px-4'"
               class="flex items-center space-x-3 py-3.5 rounded-xl transition-all duration-300 group {{ request()->routeIs('admin.users*') ? 'bg-[#1F6F5F]/10 text-[#2FA084] border-l-4 border-[#2FA084]' : 'hover:bg-gray-800 hover:text-white' }}">
                <x-icon name="o-users" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.users*') ? 'text-[#2FA084]' : 'text-gray-500 group-hover:text-[#2FA084]' }}" />
                <span x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="font-semibold text-sm whitespace-nowrap">Users</span>
            </a>

            <a href="{{ route('admin.products') }}" wire:navigate
               x-bind:class="$store.adminSidebar.collapsed ? 'justify-center' : 'px-4'"
               class="flex items-center space-x-3 py-3.5 rounded-xl transition-all duration-300 group {{ request()->routeIs('admin.products*') ? 'bg-[#1F6F5F]/10 text-[#2FA084] border-l-4 border-[#2FA084]' : 'hover:bg-gray-800 hover:text-white' }}">
                <x-icon name="o-shopping-bag" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.products*') ? 'text-[#2FA084]' : 'text-gray-500 group-hover:text-[#2FA084]' }}" />
                <span x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="font-semibold text-sm whitespace-nowrap">Products</span>
            </a>

            <a href="{{ route('admin.seller-requests') }}" wire:navigate
               x-bind:class="$store.adminSidebar.collapsed ? 'justify-center' : 'px-4'"
               class="flex items-center space-x-3 py-3.5 rounded-xl transition-all duration-300 group {{ request()->routeIs('admin.seller-requests') ? 'bg-[#1F6F5F]/10 text-[#2FA084] border-l-4 border-[#2FA084]' : 'hover:bg-gray-800 hover:text-white' }}">
                <x-icon name="o-user-plus" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.seller-requests') ? 'text-[#2FA084]' : 'text-gray-500 group-hover:text-[#2FA084]' }}" />
                <span x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="font-semibold text-sm whitespace-nowrap">Seller Requests</span>
            </a>

            <a href="{{ route('admin.auction-application') }}" wire:navigate
               x-bind:class="$store.adminSidebar.collapsed ? 'justify-center' : 'px-4'"
               class="flex items-center space-x-3 py-3.5 rounded-xl transition-all duration-300 group {{ request()->routeIs('admin.auction-application*') ? 'bg-[#1F6F5F]/10 text-[#2FA084] border-l-4 border-[#2FA084]' : 'hover:bg-gray-800 hover:text-white' }}">
                <x-icon name="o-check-badge" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.auction-application*') ? 'text-[#2FA084]' : 'text-gray-500 group-hover:text-[#2FA084]' }}" />
                <span x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="font-semibold text-sm whitespace-nowrap">Auction Applications</span>
            </a>


            <div class="pt-6">
                <p x-show="!$store.adminSidebar.collapsed" class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-3">System</p>

                <a href="{{ route('admin.settings') }}" wire:navigate
                   x-bind:class="$store.adminSidebar.collapsed ? 'justify-center' : 'px-4'"
                   class="flex items-center space-x-3 py-3.5 rounded-xl transition-all duration-300 group {{ request()->routeIs('admin.settings') ? 'bg-[#1F6F5F]/10 text-[#2FA084] border-l-4 border-[#2FA084]' : 'hover:bg-gray-800 hover:text-white' }}">
                    <x-icon name="o-cog-6-tooth" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.settings') ? 'text-[#2FA084]' : 'text-gray-500 group-hover:text-[#2FA084]' }}" />
                    <span x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="font-semibold text-sm whitespace-nowrap">Settings</span>
                </a>
            </div>
        </div>

        <!-- Footer / Logout -->
        <div class="p-4 border-t border-gray-800/50 shrink-0">
            <button wire:click="logout"
                    x-bind:class="$store.adminSidebar.collapsed ? 'justify-center' : 'px-4'"
                    class="w-full flex items-center space-x-3 py-3.5 rounded-xl text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all duration-300 group cursor-pointer">
                <x-icon name="o-arrow-left-on-rectangle" class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" />
                <span x-show="!$store.adminSidebar.collapsed" x-transition.opacity.duration.300ms class="font-bold text-sm whitespace-nowrap">Sign Out</span>
            </button>
        </div>
    </div>

    <!-- Collapse Toggle Button (Desktop) -->
    <button @click="$store.adminSidebar.toggle()"
            x-bind:class="$store.adminSidebar.collapsed ? 'lg:left-14.5' : 'lg:left-66.5'"
            class="hidden lg:flex fixed top-15 z-60 w-10 h-10 bg-[#2FA084] text-white rounded-xl cursor-pointer items-center justify-center shadow-lg hover:scale-110 transition-all duration-300 group">
        <x-icon name="o-chevron-left" class="w-4 h-4 transition-transform duration-300" x-bind:class="$store.adminSidebar.collapsed ? 'rotate-180' : ''" />
    </button>
</aside>
