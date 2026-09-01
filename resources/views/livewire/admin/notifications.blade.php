<div>
    <x-header title="Notifications" subtitle="All admin notifications in one place" separator progress-indicator>
        <x-slot:actions>
            @if($unreadCount > 0)
                <x-button
                    label="Mark All Read"
                    icon="o-check-badge"
                    class="btn-primary"
                    wire:click="markAllAsRead"
                    spinner="markAllAsRead"
                />
            @endif
        </x-slot:actions>
    </x-header>

    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden mt-6">
        <div class="p-5 border-b border-gray-100 bg-gray-50/40 flex items-center justify-between">
            <p class="text-sm font-black text-gray-800 uppercase tracking-wider">Notification Feed</p>
            <x-badge value="{{ $unreadCount }} Unread" class="badge-warning font-bold" />
        </div>

        @forelse($notifications as $notification)
            <div @class([
                'p-5 flex gap-4 border-b border-gray-50',
                'bg-blue-50/30' => !$notification->read_at
            ])>
                <div class="w-11 h-11 rounded-xl overflow-hidden border border-gray-100 shadow-sm shrink-0">
                    @if(!empty($notification->data['user_avatar']))
                        <img src="{{ Storage::url($notification->data['user_avatar']) }}" class="w-full h-full object-cover" />
                    @else
                        <img src="{{ asset('assets/images/user.png') }}" class="w-full h-full object-cover" />
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2">
                        <a
                            href="{{ $this->notificationRoute($notification) }}"
                            wire:click="markAsRead('{{ $notification->id }}')"
                            wire:navigate
                            class="block min-w-0"
                        >
                            <p class="text-sm font-bold text-gray-900">{{ $notification->data['user_name'] ?? 'System' }}</p>
                            <p class="text-sm text-gray-700 mt-1">{{ $notification->data['message'] ?? 'Notification' }}</p>
                            <p class="text-[11px] text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                        </a>
                        <div class="flex items-center gap-2">
                            @if(!$notification->read_at)
                                <x-badge value="Unread" class="badge-info text-[10px] font-bold uppercase" />
                            @else
                                <x-badge value="Read" class="badge-ghost text-[10px] font-bold uppercase" />
                            @endif

                            @if(!$notification->read_at)
                                <x-button
                                    label="Mark Read"
                                    icon="o-check"
                                    class="btn-ghost btn-sm"
                                    wire:click="markAsRead('{{ $notification->id }}')"
                                    spinner
                                />
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-14 text-center">
                <x-icon name="o-bell-slash" class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-500 font-semibold">No notifications available.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
