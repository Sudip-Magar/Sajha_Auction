<div class="space-y-6">
    <x-header title="Auction Application Detail" subtitle="Inspect applicant information and uploaded identity documents" separator progress-indicator>
        <x-slot:actions>
            <a href="{{ route('admin.auction-application') }}" wire:navigate>
                <x-button label="Back to Applications" icon="o-arrow-left" class="btn-ghost" />
            </a>
        </x-slot:actions>
    </x-header>

    @php($status = $this->applicationStatus())

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <x-icon name="o-identification" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Documents</p>
                <p class="text-2xl font-black text-gray-900">{{ $user->documentImages->count() }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <x-icon name="o-user" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Applicant</p>
                <p class="text-lg font-black text-gray-900">{{ $user->name }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <x-icon name="o-clock" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Submitted</p>
                <p class="text-lg font-black text-gray-900">{{ $user->documentImages->max('created_at')?->format('M d, Y h:i A') }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl {{ $status === 'approved' ? 'bg-emerald-50 text-emerald-600' : ($status === 'rejected' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600') }} flex items-center justify-center">
                <x-icon name="o-shield-check" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Status</p>
                <p class="text-2xl font-black {{ $status === 'approved' ? 'text-emerald-600' : ($status === 'rejected' ? 'text-rose-600' : 'text-amber-500') }}">
                    {{ str($status)->headline() }}
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.5fr)_420px] gap-4">
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Uploaded Documents</p>
                </div>

                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($user->documentImages as $document)
                        <div class="rounded-2xl border border-gray-200 overflow-hidden bg-white">
                            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-gray-800">{{ $document->type->label() }}</p>
                                    <p class="text-[11px] text-gray-500">{{ $document->created_at?->format('M d, Y h:i A') }}</p>
                                </div>

                                <x-badge
                                    :value="$document->is_approved ? 'Approved' : ($document->is_rejected ? 'Rejected' : 'Pending')"
                                    :class="$document->is_approved ? 'badge-success' : ($document->is_rejected ? 'badge-error' : 'badge-warning')"
                                    class="font-bold text-[10px] uppercase tracking-wider"
                                />
                            </div>

                            <a href="{{ Storage::url($document->image) }}" target="_blank" class="block bg-[#f5f2ea]">
                                <img src="{{ Storage::url($document->image) }}" alt="{{ $document->type->label() }}" class="w-full h-72 object-contain bg-white" />
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Applicant Information</p>
                </div>

                <div class="p-4 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-black overflow-hidden">
                            @if($user->avatar)
                                <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                            @else
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            @endif
                        </div>

                        <div class="min-w-0">
                            <p class="text-sm font-black text-gray-900 truncate">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4 space-y-3">
                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Username</p>
                            <p class="mt-2 text-sm font-black text-gray-900">{{ $user->username ?? 'Not available' }}</p>
                        </div>

                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Phone</p>
                            <p class="mt-2 text-sm font-black text-gray-900">{{ $user->phone ?? 'Not available' }}</p>
                        </div>

                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Auction Access</p>
                            <div class="mt-2 inline-flex items-center gap-2 text-xs font-bold {{ $user->is_auction_allowed ? 'text-emerald-600' : 'text-gray-500' }}">
                                <span class="w-2 h-2 rounded-full {{ $user->is_auction_allowed ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                {{ $user->is_auction_allowed ? 'Allowed to bid' : 'Not yet allowed' }}
                            </div>
                        </div>

                        @if($user->bio)
                            <div class="rounded-xl bg-[#f5f2ea] p-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Bio</p>
                                <p class="mt-2 text-sm leading-6 text-gray-700">{{ $user->bio }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Review Action</p>
                </div>

                <div class="p-4 space-y-3">
                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Current Review State</p>
                        <p class="mt-2 text-sm font-black text-gray-900">{{ str($status)->headline() }}</p>
                    </div>

                    <div class="border-t border-gray-200 pt-4 flex items-center gap-3">
                        @if($status !== 'approved')
                            <x-button
                                label="Approve"
                                icon="o-check"
                                class="btn-success flex-1 rounded-xl"
                                wire:click="approveApplication"
                                spinner="approveApplication"
                            />
                        @endif

                        @if($status !== 'rejected')
                            <x-button
                                label="Reject"
                                icon="o-x-mark"
                                class="btn-error flex-1 rounded-xl"
                                wire:click="rejectApplication"
                                spinner="rejectApplication"
                            />
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
