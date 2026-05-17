<div>
    <x-header title="Seller Requests" subtitle="Review and approve user applications for seller status" separator progress-indicator />

    <div class="mb-8 flex flex-col md:flex-row gap-4 justify-between items-center">
        <div class="flex items-center gap-4">
            <x-badge value="{{ $pendingCount }} Pending" class="badge-warning font-bold" />
            <x-badge value="{{ $approvedCount }} Approved" class="badge-success font-bold" />
        </div>
        <div class="w-full md:w-80">
            <x-input placeholder="Search applicants..." wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($users as $user)
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:shadow-gray-200/50 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="p-6 flex-1">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden border-2 border-white shadow-md bg-gray-50">
                            @if($user->avatar)
                                <img src="{{ Storage::url($user->avatar) }}" class="w-full h-full object-cover">
                            @else
                                <img src="{{ asset('assets/images/user.png') }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <x-badge value="Pending Review"
                            class="badge-warning font-bold text-[10px] uppercase tracking-wider" />
                    </div>

                    <h3 class="text-lg font-black text-gray-900 truncate">{{ $user->name }}</h3>
                    <p class="text-xs text-gray-400 font-medium mb-4">{{ $user->email }}</p>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <x-icon name="o-calendar" class="w-4 h-4 text-gray-400" />
                            <span>Joined {{ $user->created_at->format('M Y') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <x-icon name="o-phone" class="w-4 h-4 text-gray-400" />
                            <span>{{ $user->phone ?? 'No phone provided' }}</span>
                        </div>
                    </div>

                    @if($user->bio)
                        <div class="mt-4 p-3 bg-gray-50 rounded-xl text-xs text-gray-600 line-clamp-2 italic">
                            "{{ $user->bio }}"
                        </div>
                    @endif
                </div>

                <div class="p-4 bg-gray-50/50 border-t border-gray-100 flex gap-2">
                    <x-button label="Approve Application" icon="o-check"
                        class="btn-primary btn-sm flex-1 rounded-xl shadow-md shadow-primary/20"
                        wire:click="approveSeller({{ $user->id }})"
                        spinner />
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-icon name="o-users" class="w-10 h-10 text-gray-200" />
                </div>
                <h3 class="text-gray-400 font-bold uppercase tracking-widest text-sm">No applications found</h3>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $users->links() }}
    </div>
</div>
