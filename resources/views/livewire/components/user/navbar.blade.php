<nav x-data="{ mobileMenuOpen: false, userDropdownOpen: false }"
     class="sticky top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all duration-300">
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-15">
            <!-- Logo Area -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center group">
                    <div
                        class="w-10 h-10 bg-linear-to-br from-[#1F6F5F] to-[#2FA084] rounded-xl flex items-center justify-center shadow-lg shadow-[#2FA084]/20 group-hover:scale-110 transition-transform duration-300">
                        <x-icon name="o-bolt" class="w-6 h-6 text-white"/>
                    </div>
                    <span
                        class="ml-3 text-2xl font-black tracking-tight bg-clip-text text-transparent bg-linear-to-r from-[#1F6F5F] to-[#2FA084]">
                        Sajha<span class="text-gray-900">Auction</span>
                    </span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-8">
                <a
                    href="{{ route('home') }}"
                    wire:navigate
                    @class([
                        'text-sm font-semibold transition-colors relative group px-1 py-2',
                        'text-[#1F6F5F]' => request()->routeIs('home'),
                        'text-gray-600 hover:text-[#2FA084]' => ! request()->routeIs('home'),
                    ])
                >
                    Home
                    <span @class([
                        'absolute -bottom-1 left-0 h-0.5 bg-[#2FA084] transition-all duration-300',
                        'w-full' => request()->routeIs('home'),
                        'w-0 group-hover:w-full' => ! request()->routeIs('home'),
                    ])></span>
                </a>
                @if (Auth::user()?->is_seller)
                    <a
                        href="{{ route('dashboard') }}"
                        wire:navigate
                        @class([
                            'text-sm font-semibold transition-colors relative group px-1 py-2',
                            'text-[#1F6F5F]' => request()->routeIs('dashboard'),
                            'text-gray-600 hover:text-[#2FA084]' => ! request()->routeIs('dashboard'),
                        ])
                    >
                        Dashboard
                        <span @class([
                            'absolute -bottom-1 left-0 h-0.5 bg-[#2FA084] transition-all duration-300',
                            'w-full' => request()->routeIs('dashboard'),
                            'w-0 group-hover:w-full' => ! request()->routeIs('dashboard'),
                        ])></span>
                    </a>
                @endif
                <a
                    href="{{ route('user.marketplace-products') }}"
                    wire:navigate
                    @class([
                        'text-sm font-semibold transition-colors relative group px-1 py-2',
                        'text-[#1F6F5F]' => request()->routeIs('user.marketplace-products'),
                        'text-gray-600 hover:text-[#2FA084]' => ! request()->routeIs('user.marketplace-products'),
                    ])
                >
                    Products
                    <span @class([
                        'absolute -bottom-1 left-0 h-0.5 bg-[#2FA084] transition-all duration-300',
                        'w-full' => request()->routeIs('user.marketplace-products'),
                        'w-0 group-hover:w-full' => ! request()->routeIs('user.marketplace-products'),
                    ])></span>
                </a>

                <a
                    href="{{ route('user.auction') }}"
                    wire:navigate
                    @class([
                        'text-sm font-semibold transition-colors relative group px-1 py-2',
                        'text-[#1F6F5F]' => request()->routeIs('user.auction'),
                        'text-gray-600 hover:text-[#2FA084]' => ! request()->routeIs('user.auction'),
                    ])
                >
                    Auction
                    <span @class([
                        'absolute -bottom-1 left-0 h-0.5 bg-[#2FA084] transition-all duration-300',
                        'w-full' => request()->routeIs('user.auction'),
                        'w-0 group-hover:w-full' => ! request()->routeIs('user.auction'),
                    ])></span>
                </a>

                <a href="#"
                   class="text-sm font-semibold text-gray-600 hover:text-[#2FA084] transition-colors relative group">
                    How it Works
                    <span
                        class="absolute -bottom-1 left-0 w-0 h-0.5 bg-[#2FA084] transition-all duration-300 group-hover:w-full"></span>
                </a>
                @if (Auth::user() && Auth::user()->is_seller)
                    <a
                        href="{{ route('user.products') }}"
                        wire:navigate
                        @class([
                            'text-sm font-semibold transition-colors relative group px-1 py-2',
                            'text-[#1F6F5F]' => request()->routeIs('user.products'),
                            'text-gray-600 hover:text-[#2FA084]' => ! request()->routeIs('user.products'),
                        ])
                    >
                        My Products
                        <span @class([
                            'absolute -bottom-1 left-0 h-0.5 bg-[#2FA084] transition-all duration-300',
                            'w-full' => request()->routeIs('user.products'),
                            'w-0 group-hover:w-full' => ! request()->routeIs('user.products'),
                        ])></span>
                    </a>
                @endif
            </div>

            <!-- Right Side Actions -->
            <div class="hidden md:flex items-center space-x-4">
                @guest
                    <a href="{{ route('user.login') }}" wire:navigate
                       class="text-sm font-bold text-gray-700 hover:text-[#1F6F5F] px-4 py-2 transition-colors">
                        Sign In
                    </a>
                    <a href="{{ route('user.register') }}" wire:navigate
                       class="bg-linear-to-r from-[#1F6F5F] to-[#2FA084] text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-[#2FA084]/20 hover:shadow-[#2FA084]/40 hover:-translate-y-0.5 transition-all duration-300">
                        Get Started
                    </a>
                @endguest

                @auth
                    <!-- User Notifications -->
                    <x-dropdown right>
                        <x-slot:trigger>
                            <button
                                class="relative p-2 text-gray-500 hover:text-[#2FA084] transition-colors cursor-pointer bg-gray-50 rounded-xl">
                                <x-icon name="o-bell" class="w-6 h-6"/>
                                @php
                                    $unreadCount = $notifications->where('read_at', null)->count();
                                @endphp
                                @if($unreadCount > 0)
                                    <span
                                        class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                                @endif
                            </button>
                        </x-slot:trigger>

                        <div class="w-80 max-h-96 overflow-y-auto overflow-x-hidden">
                            <div class="p-4 border-b border-gray-50 bg-gray-50/30 flex justify-between items-center">
                                <h3 class="font-black text-xs uppercase tracking-widest text-gray-800">Alerts</h3>
                                <span
                                    class="text-[10px] font-bold text-[#2FA084] uppercase">{{ $unreadCount }} New</span>
                            </div>

                            @forelse($notifications as $notification)
                                <div wire:click="handleNotificationClick('{{ $notification->id }}')"
                                    @class([
                                       'p-4 flex gap-3 cursor-pointer transition-all border-b border-gray-50 hover:bg-gray-50',
                                       'bg-blue-50/20' => !$notification->read_at
                                    ])>
                                    <div
                                        class="shrink-0 w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                                        <x-icon name="o-information-circle" class="w-5 h-5"/>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p @class(['text-xs leading-relaxed', 'font-black text-gray-900' => !$notification->read_at, 'text-gray-500' => $notification->read_at])>
                                            {{ $notification->data['message'] }}
                                        </p>
                                        <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="p-10 text-center">
                                    <x-icon name="o-bell-slash" class="w-10 h-10 text-gray-200 mx-auto mb-2"/>
                                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">All caught
                                        up!</p>
                                </div>
                            @endforelse

                            <div class="p-3 border-t border-gray-100 bg-gray-50/40">
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        wire:click="markAllAsRead"
                                        class="flex-1 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-[#1F6F5F] hover:bg-[#1F6F5F]/10 transition-colors cursor-pointer"
                                    >
                                        Marked as Read
                                    </button>
                                    <a
                                        href="{{ route('user.notifications') }}"
                                        wire:navigate
                                        class="flex-1 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-[#1F6F5F] hover:bg-[#1F6F5F]/10 transition-colors"
                                    >
                                        Show More
                                    </a>
                                </div>
                            </div>
                        </div>
                    </x-dropdown>

                    <div class="relative" @click.away="userDropdownOpen = false">
                        <button @click="userDropdownOpen = !userDropdownOpen"
                                class="flex items-center space-x-3 p-1.5 rounded-xl cursor-pointer hover:bg-gray-50 transition-all duration-300 focus:outline-none">
                            <div class="text-right mr-2 hidden lg:block">
                                <p class="text-sm font-bold text-gray-900 leading-none">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 mt-1 uppercase tracking-wider font-semibold">User</p>
                            </div>
                            <div class="w-10 h-10 rounded-xl overflow-hidden border-2 border-[#2FA084]/20 shadow-sm">
                                @if(auth()->user()->avatar)
                                    <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="Avatar"
                                         class="w-full h-full object-cover">
                                @else
                                    <div
                                        class="w-full h-full bg-linear-to-br from-[#1F6F5F] to-[#2FA084] flex items-center justify-center text-white font-bold">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <x-icon name="o-chevron-down"
                                    class="w-4 h-4 text-gray-400 transition-transform duration-300"
                                    ::class="userDropdownOpen ? 'rotate-180' : ''"/>
                        </button>

                        <!-- User Dropdown Menu -->
                        <div x-show="userDropdownOpen"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                             class="absolute right-0 mt-3 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 z-50 overflow-hidden"
                             style="display: none;">
                            <div class="px-4 py-3 border-b border-gray-50 mb-1">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Account</p>
                            </div>
                            <a href="{{ route('user.settings') }}" wire:navigate
                               class="flex items-center space-x-3 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2FA084] transition-all">
                                <x-icon name="o-user" class="w-4 h-4"/>
                                <span>My Profile</span>
                            </a>
                            @if($isAuctioner)
                                @if (!$isSeller)
                                    @if ($sellerApplicationPending)
                                        <div
                                            class="flex cursor-not-allowed items-center space-x-3 px-4 py-2.5 text-sm font-medium text-amber-600 bg-amber-50">
                                            <x-icon name="o-clock" class="w-4 h-4"/>
                                            <span>Seller Request Pending</span>
                                        </div>
                                    @else
                                        <button type="button"
                                                wire:click="requestSellerAccess"
                                                wire:loading.attr="disabled"
                                                wire:target="requestSellerAccess"
                                                class="w-full flex items-center cursor-pointer space-x-3 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2FA084] transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                            <x-icon name="o-user-plus" class="w-4 h-4"/>
                                            <span wire:loading.remove
                                                  wire:target="requestSellerAccess">Seller Request</span>
                                            <span wire:loading
                                                  wire:target="requestSellerAccess">Sending Request...</span>
                                        </button>
                                    @endif
                                @endif
                                <a href="#"
                                   class="flex items-center space-x-3 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2FA084] transition-all">
                                    <x-icon name="o-shopping-bag" class="w-4 h-4"/>
                                    <span>My Bids</span>
                                </a>
                                <a href="#"
                                   class="flex items-center space-x-3 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2FA084] transition-all">
                                    <x-icon name="o-gift" class="w-4 h-4"/>
                                    <span>My Auctions</span>
                                </a>
                            @else
                                <a href="{{route('user.join-auction')}}"
                                   class="flex items-center space-x-3 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2FA084] transition-all">
                                    <x-icon name="o-hand-raised" class="w-4 h-4"/>
                                    <span>Join Auction</span>
                                </a>
                            @endif
                            <div class="h-px bg-gray-50 my-1"></div>
                            <button wire:click="logout"
                                    class="w-full flex items-center space-x-3 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-all">
                                <x-icon name="o-arrow-left-on-rectangle" class="w-4 h-4"/>
                                <span>Sign Out</span>
                            </button>
                        </div>
                    </div>
                @endauth
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden flex items-center">
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 rounded-xl text-gray-600 hover:bg-gray-100 transition-all duration-300 focus:outline-none">
                    <x-icon name="o-bars-3-bottom-right" x-show="!mobileMenuOpen" class="w-7 h-7"/>
                    <x-icon name="o-x-mark" x-show="mobileMenuOpen" class="w-7 h-7" style="display: none;"/>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="md:hidden bg-white border-t border-gray-50 overflow-hidden"
         style="display: none;">
        <div class="px-4 pt-4 pb-6 space-y-2">
            <a
                href="{{ route('home') }}"
                wire:navigate
                @class([
                    'block px-4 py-3 rounded-xl text-base font-bold transition-all',
                    'bg-[#2FA084]/10 text-[#1F6F5F]' => request()->routeIs('home'),
                    'text-gray-700 hover:bg-gray-50 hover:text-[#2FA084]' => ! request()->routeIs('home'),
                ])
            >
                Home
            </a>
            @if (Auth::user()?->is_seller)
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    @class([
                        'block px-4 py-3 rounded-xl text-base font-bold transition-all',
                        'bg-[#2FA084]/10 text-[#1F6F5F]' => request()->routeIs('dashboard'),
                        'text-gray-700 hover:bg-gray-50 hover:text-[#2FA084]' => ! request()->routeIs('dashboard'),
                    ])
                >
                    Dashboard
                </a>
            @endif
            <a
                href="{{ route('user.marketplace-products') }}"
                wire:navigate
                @class([
                    'block px-4 py-3 rounded-xl text-base font-bold transition-all',
                    'bg-[#2FA084]/10 text-[#1F6F5F]' => request()->routeIs('user.marketplace-products'),
                    'text-gray-700 hover:bg-gray-50 hover:text-[#2FA084]' => ! request()->routeIs('user.marketplace-products'),
                ])
            >
                Products
            </a>
            <a
                href="{{ route('user.auction') }}"
                wire:navigate
                @class([
                    'block px-4 py-3 rounded-xl text-base font-bold transition-all',
                    'bg-[#2FA084]/10 text-[#1F6F5F]' => request()->routeIs('user.auction'),
                    'text-gray-700 hover:bg-gray-50 hover:text-[#2FA084]' => ! request()->routeIs('user.auction'),
                ])
            >
                Auction
            </a>
            <a href="#"
               class="block px-4 py-3 rounded-xl text-base font-bold text-gray-700 hover:bg-gray-50 hover:text-[#2FA084] transition-all">
                How it Works
            </a>
            @if (Auth::user() && Auth::user()->is_seller)
                <a
                    href="{{ route('user.products') }}"
                    wire:navigate
                    @class([
                        'block px-4 py-3 rounded-xl text-base font-bold transition-all',
                        'bg-[#2FA084]/10 text-[#1F6F5F]' => request()->routeIs('user.products'),
                        'text-gray-700 hover:bg-gray-50 hover:text-[#2FA084]' => ! request()->routeIs('user.products'),
                    ])
                >
                    My Products
                </a>
            @endif

            @guest
                <div class="pt-4 grid grid-cols-2 gap-3">
                    <a href="{{ route('user.login') }}" wire:navigate
                       class="flex items-center justify-center px-4 py-3 rounded-xl text-sm font-bold text-gray-700 border border-gray-200 hover:bg-gray-50 transition-all">
                        Sign In
                    </a>
                    <a href="{{ route('user.register') }}" wire:navigate
                       class="flex items-center justify-center px-4 py-3 rounded-xl text-sm font-bold text-white bg-linear-to-r from-[#1F6F5F] to-[#2FA084] shadow-lg shadow-[#2FA084]/20 transition-all">
                        Get Started
                    </a>
                </div>
            @endguest

            @auth
                <div class="pt-4 mt-4 border-t border-gray-100">
                    <div class="flex items-center px-4 mb-4">
                        <div class="w-12 h-12 rounded-xl overflow-hidden border-2 border-[#2FA084]/20 mr-4">
                            @if(auth()->user()->avatar)
                                <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="Avatar"
                                     class="w-full h-full object-cover">
                            @else
                                <div
                                    class="w-full h-full bg-linear-to-br from-[#1F6F5F] to-[#2FA084] flex items-center justify-center text-white font-bold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <p class="text-base font-bold text-gray-900 leading-none">{{ auth()->user()->name }}</p>
                            <p class="text-sm text-gray-500 mt-1 uppercase tracking-wider font-semibold">User
                                Account</p>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <a href="#"
                           class="flex items-center space-x-3 px-4 py-3 rounded-xl text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2FA084] transition-all">
                            <x-icon name="o-user" class="w-5 h-5"/>
                            <span>My Profile</span>
                        </a>
                        @if (! $isSeller)
                            @if ($sellerApplicationPending)
                                <div
                                    class="flex items-center space-x-3 px-4 py-3 rounded-xl text-base font-medium text-amber-600 bg-amber-50">
                                    <x-icon name="o-clock" class="w-5 h-5"/>
                                    <span>Seller Request Pending</span>
                                </div>
                            @else
                                <button type="button"
                                        wire:click="requestSellerAccess"
                                        wire:loading.attr="disabled"
                                        wire:target="requestSellerAccess"
                                        class="w-full cursor-pointer flex items-center space-x-3 px-4 py-3 rounded-xl text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2FA084] transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                                    <x-icon name="o-user-plus" class="w-5 h-5"/>
                                    <span wire:loading.remove
                                          wire:target="requestSellerAccess">Request Seller Access</span>
                                    <span wire:loading wire:target="requestSellerAccess">Sending Request...</span>
                                </button>
                            @endif
                        @endif
                        <a href="#"
                           class="flex items-center space-x-3 px-4 py-3 rounded-xl text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-[#2FA084] transition-all">
                            <x-icon name="o-shopping-bag" class="w-5 h-5"/>
                            <span>My Bids</span>
                        </a>
                        <button wire:click="logout" type="button"
                                class="w-full cursor-pointer flex items-center space-x-3 px-4 py-3 rounded-xl text-base font-medium text-red-600 hover:bg-red-50 transition-all">
                            <x-icon name="o-arrow-left-on-rectangle" class="w-5 h-5"/>
                            <span>Sign Out</span>
                        </button>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</nav>
