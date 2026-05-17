<header class="sticky top-0 z-30 bg-linear-to-r from-[#2FA084] to-[#1F6F5F] backdrop-blur-md border-b border-white/10 shadow-sm h-12 flex items-center px-8 lg:px-12 justify-between">
    <h2 class="text-xl font-bold text-white">{{ $title ?? 'Dashboard' }}</h2>
    <div class="flex items-center space-x-4">
        <x-dropdown right>
            <x-slot:trigger>
                <button class="relative p-2 text-white/70 hover:text-white transition-colors cursor-pointer">
                    <x-icon name="o-bell" class="w-6 h-6" />
                    @php
                        $unreadCount = $notifications->where('read_at', null)->count();
                    @endphp
                    @if($unreadCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full text-[0.65rem] font-bold flex items-center justify-center bg-red-500 text-white leading-none">
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                    @endif
                </button>
            </x-slot:trigger>

            <div class="w-85 max-h-[32rem] overflow-y-auto overflow-x-hidden">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-sm text-gray-800">Notifications</h3>
                    <span class="px-2 py-0.5 rounded-full bg-[#1F6F5F]/10 text-[#1F6F5F] text-[10px] font-bold uppercase tracking-wider">{{ $unreadCount }} New</span>
                </div>

                @forelse($notifications as $notification)
                    <div wire:click="handleNotificationClick('{{ $notification->id }}')"
                         @class([
                            'p-4 flex gap-3 cursor-pointer transition-all duration-200 border-b border-gray-50 hover:bg-gray-50 relative',
                            'bg-blue-50/30' => !$notification->read_at
                         ])>
                        @if(!$notification->read_at)
                            <div class="absolute left-1 top-1/2 -translate-y-1/2 w-1 h-8 bg-[#2FA084] rounded-full"></div>
                        @endif

                        <div class="shrink-0">
                            @php
                                $avatar = $notification->data['user_avatar'] ?? null;
                                $name = $notification->data['user_name'] ?? 'User';
                            @endphp
                            <div class="w-11 h-11 rounded-xl overflow-hidden border border-gray-100 shadow-sm">
                                @if($avatar)
                                    <img src="{{ Storage::url($avatar) }}" class="w-full h-full object-cover">
                                @else
                                    <img src="{{ asset('assets/images/user.png') }}" class="w-full h-full object-cover">
                                @endif
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 truncate">{{ $name }}</p>
                            <p class="text-xs text-gray-600 line-clamp-2 mt-0.5">{{ $notification->data['message'] }}</p>
                            <p class="text-[10px] text-gray-400 mt-1.5 flex items-center gap-1">
                                <x-icon name="o-clock" class="w-3 h-3" />
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-icon name="o-bell-slash" class="w-8 h-8 text-gray-300" />
                        </div>
                        <p class="text-gray-400 text-sm font-medium">No notifications yet</p>
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
                            href="{{ route('admin.notifications') }}"
                            wire:navigate
                            class="flex-1 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-[#1F6F5F] hover:bg-[#1F6F5F]/10 transition-colors"
                        >
                            Show More
                        </a>
                    </div>
                </div>
            </div>
        </x-dropdown>
    </div>
</header>
