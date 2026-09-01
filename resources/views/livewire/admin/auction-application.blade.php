<div class="space-y-6">
    <x-header title="Auction Applications" subtitle="Review identity documents before granting auction bidding access" separator progress-indicator />

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Submitted</p>
            <p class="mt-2 text-3xl font-black text-gray-900">{{ $pendingCount + $approvedCount + $rejectedCount }}</p>
            <p class="mt-1 text-xs text-gray-500">Users with uploaded auction documents.</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Pending</p>
            <p class="mt-2 text-3xl font-black text-amber-500">{{ $pendingCount }}</p>
            <p class="mt-1 text-xs text-gray-500">Waiting for admin review.</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Approved</p>
            <p class="mt-2 text-3xl font-black text-emerald-600">{{ $approvedCount }}</p>
            <p class="mt-1 text-xs text-gray-500">Users allowed to join auctions.</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Rejected</p>
            <p class="mt-2 text-3xl font-black text-rose-600">{{ $rejectedCount }}</p>
            <p class="mt-1 text-xs text-gray-500">Need clearer document resubmission.</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/30">
            <div class="flex items-center gap-2">
                <div class="w-1 bg-[#2FA084] h-6 rounded-full"></div>
                <h3 class="font-black text-gray-800 uppercase tracking-tighter">Application Queue</h3>
            </div>

            <div class="w-full md:w-80">
                <x-input placeholder="Search applicants..." wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" class="bg-white" />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'id', 'label' => 'ID', 'class' => 'w-16 text-gray-400'],
                ['key' => 'name', 'label' => 'Applicant'],
                ['key' => 'username', 'label' => 'Username'],
                ['key' => 'documents_count', 'label' => 'Documents'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'actions', 'label' => '', 'sortable' => false],
            ];
        @endphp

        <x-table :headers="$headers" :rows="$applications" with-pagination>
            @scope('cell_name', $user)
                <div class="flex items-center gap-4 py-1">
                    <div class="w-12 h-12 rounded-xl overflow-hidden border border-gray-100 shadow-inner bg-gray-50 flex items-center justify-center shrink-0">
                        @if($user->avatar)
                            <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                        @else
                            <span class="text-sm font-black text-gray-500">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <div class="font-black text-gray-900 truncate">{{ $user->name }}</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest truncate">{{ $user->email }}</div>
                    </div>
                </div>
            @endscope

            @scope('cell_username', $user)
                <div class="text-xs font-bold text-gray-600">
                    {{ $user->username ?? 'Not set' }}
                </div>
            @endscope

            @scope('cell_documents_count', $user)
                <div class="flex flex-col">
                    <span class="font-black text-gray-900 text-sm">{{ $user->documentImages->count() }}</span>
                    <span class="text-[9px] uppercase font-black text-gray-400 tracking-tighter">Uploaded files</span>
                </div>
            @endscope

            @scope('cell_status', $user)
                @php($status = $this->applicationStatus($user))

                <x-badge
                    :value="str($status)->headline()"
                    :class="match ($status) {
                        'approved' => 'badge-success',
                        'rejected' => 'badge-error',
                        default => 'badge-warning',
                    }"
                    class="font-bold text-[10px] uppercase tracking-wider"
                />
            @endscope

            @scope('actions', $user)
                @php($status = $this->applicationStatus($user))

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.auction-application.show', $user) }}" wire:navigate>
                        <x-button label="View" icon="o-eye" class="btn-sm btn-info rounded-xl shadow-md shadow-info/20" />
                    </a>

                    @if($status !== 'approved')
                        <x-button
                            label="Approve"
                            icon="o-check"
                            class="btn-sm btn-success rounded-xl shadow-md shadow-success/20"
                            wire:click="approveApplication({{ $user->id }})"
                            spinner
                        />
                    @endif

                    @if($status !== 'rejected')
                        <x-button
                            label="Reject"
                            icon="o-x-mark"
                            class="btn-sm btn-error rounded-xl shadow-md shadow-error/20"
                            wire:click="rejectApplication({{ $user->id }})"
                            spinner
                        />
                    @endif
                </div>
            @endscope
        </x-table>

        @if($applications->isEmpty())
            <div class="py-20 text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-icon name="o-identification" class="w-10 h-10 text-gray-200" />
                </div>
                <h3 class="text-gray-400 font-bold uppercase tracking-widest text-sm">No auction applications found</h3>
            </div>
        @endif
    </div>
</div>
