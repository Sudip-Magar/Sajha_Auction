<div class="space-y-6">
    <x-header title="User Detail" subtitle="Review account information and control access from one place" separator progress-indicator>
        <x-slot:actions>
            <a href="{{ route('admin.users') }}" wire:navigate>
                <x-button label="Back to Users" icon="o-arrow-left" class="btn-ghost" />
            </a>
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <x-icon name="o-user" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Account Status</p>
                <p class="text-2xl font-black {{ $user->isActiveStatus() ? 'text-emerald-600' : 'text-rose-600' }}">{{ ucfirst($user->status) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <x-icon name="o-shopping-bag" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Seller Access</p>
                <p class="text-2xl font-black {{ $user->is_seller ? 'text-sky-600' : 'text-gray-500' }}">{{ $user->is_seller ? 'Enabled' : 'Disabled' }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <x-icon name="o-shield-check" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Auction Access</p>
                <p class="text-2xl font-black {{ $user->is_auction_allowed ? 'text-amber-500' : 'text-gray-500' }}">{{ $user->is_auction_allowed ? 'Allowed' : 'Blocked' }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                <x-icon name="o-identification" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Documents</p>
                <p class="text-2xl font-black text-gray-900">{{ $user->documentImages->count() }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.45fr)_420px] gap-4">
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Profile Overview</p>
                </div>

                <div class="p-5 space-y-5">
                    <div class="flex items-center gap-4">
                        <div class="w-18 h-18 rounded-2xl overflow-hidden border border-gray-200 bg-gray-50 flex items-center justify-center">
                            @if($user->avatar)
                                <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                            @else
                                <span class="text-lg font-black text-gray-500">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <p class="text-2xl font-black text-gray-900 truncate">{{ $user->name }}</p>
                            <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <x-badge :value="$user->is_verified ? 'Verified' : 'Unverified'" :class="$user->is_verified ? 'badge-success' : 'badge-warning'" class="text-[10px] font-bold uppercase tracking-wider" />
                                <x-badge :value="$user->seller_application_pending ? 'Seller Request Pending' : 'No Seller Request'" :class="$user->seller_application_pending ? 'badge-warning' : 'badge-ghost'" class="text-[10px] font-bold uppercase tracking-wider" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Username</p>
                            <p class="mt-2 text-sm font-black text-gray-900">{{ $user->username ?? 'Not available' }}</p>
                        </div>

                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Phone</p>
                            <p class="mt-2 text-sm font-black text-gray-900">{{ $user->phone ?? 'Not available' }}</p>
                        </div>

                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Gender</p>
                            <p class="mt-2 text-sm font-black text-gray-900">{{ $user->gender ?? 'Not available' }}</p>
                        </div>

                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Date of Birth</p>
                            <p class="mt-2 text-sm font-black text-gray-900">{{ $user->date_of_birth_en ?? 'Not available' }}</p>
                        </div>
                    </div>

                    @if($user->bio)
                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Bio</p>
                            <p class="mt-2 text-sm leading-7 text-gray-700">{{ $user->bio }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Auction Documents</p>
                </div>

                @if($user->documentImages->isNotEmpty())
                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($user->documentImages as $document)
                            <div class="rounded-2xl border border-gray-200 overflow-hidden">
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
                                    <img src="{{ Storage::url($document->image) }}" alt="{{ $document->type->label() }}" class="w-full h-64 object-contain bg-white" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-sm text-gray-400">No auction documents uploaded.</div>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Seller Products</p>
                </div>

                @if($user->products->isNotEmpty())
                    <div class="divide-y divide-gray-200">
                        @foreach($user->products as $product)
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden border border-gray-100 bg-gray-50 flex items-center justify-center shrink-0">
                                        @if($product->image)
                                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover" />
                                        @else
                                            <x-icon name="o-photo" class="w-5 h-5 text-gray-300" />
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-black text-gray-900 truncate">{{ $product->name }}</p>
                                        <p class="text-[11px] text-gray-500 truncate">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <x-badge :value="ucfirst($product->status)" :class="$product->status === 'active' ? 'badge-success' : 'badge-warning'" class="text-[10px] font-bold uppercase tracking-wider" />
                                    <x-badge :value="$product->is_approved ? 'Approved' : 'Pending'" :class="$product->is_approved ? 'badge-info' : 'badge-warning'" class="text-[10px] font-bold uppercase tracking-wider" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-sm text-gray-400">This user has not uploaded any products yet.</div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Access Controls</p>
                </div>

                <div class="p-4 space-y-3">
                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Current Status</p>
                        <p class="mt-2 text-sm font-black text-gray-900">{{ ucfirst($user->status) }}</p>
                    </div>

                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Seller Access</p>
                        <p class="mt-2 text-sm font-black text-gray-900">{{ $user->is_seller ? 'Seller can upload products.' : 'Seller access is disabled.' }}</p>
                    </div>

                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Auction Access</p>
                        <p class="mt-2 text-sm font-black text-gray-900">{{ $user->is_auction_allowed ? 'User can join auctions.' : 'User cannot join auctions.' }}</p>
                    </div>

                    <div class="border-t border-gray-200 pt-4 space-y-3">
                        <x-button
                            :label="$user->isActiveStatus() ? 'Set Inactive' : 'Set Active'"
                            icon="o-power"
                            :class="$user->isActiveStatus() ? 'btn-error w-full rounded-xl' : 'btn-success w-full rounded-xl'"
                            wire:click="toggleStatus"
                            spinner="toggleStatus"
                        />

                        <x-button
                            :label="$user->is_seller ? 'Suspend Seller Access' : 'Enable Seller Access'"
                            icon="o-shopping-bag"
                            :class="$user->is_seller ? 'btn-warning w-full rounded-xl' : 'btn-info w-full rounded-xl'"
                            wire:click="toggleSellerAccess"
                            spinner="toggleSellerAccess"
                        />

                        <x-button
                            :label="$user->is_auction_allowed ? 'Block Auction Access' : 'Allow Auction Access'"
                            icon="o-shield-check"
                            :class="$user->is_auction_allowed ? 'btn-error w-full rounded-xl' : 'btn-warning w-full rounded-xl'"
                            wire:click="toggleAuctionAccess"
                            spinner="toggleAuctionAccess"
                        />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Account Timeline</p>
                </div>

                <div class="p-4 space-y-3">
                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Joined At</p>
                        <p class="mt-2 text-sm font-black text-gray-900">{{ $user->created_at?->format('M d, Y h:i A') }}</p>
                    </div>

                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Updated At</p>
                        <p class="mt-2 text-sm font-black text-gray-900">{{ $user->updated_at?->format('M d, Y h:i A') }}</p>
                    </div>

                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Products Uploaded</p>
                        <p class="mt-2 text-sm font-black text-gray-900">{{ $user->products->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
