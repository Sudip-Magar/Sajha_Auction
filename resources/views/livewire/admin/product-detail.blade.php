<div class="space-y-6">
    <x-header title="Product Detail" subtitle="Review product information before approval" separator progress-indicator>
        <x-slot:actions>
            <a href="{{ route('admin.products') }}" wire:navigate>
                <x-button label="Back to Products" icon="o-arrow-left" class="btn-ghost" />
            </a>
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <x-icon name="o-banknotes" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">
                    {{ $product->type === 'sell' ? 'Selling Price' : 'Starting Bid' }}
                </p>
                <p class="text-2xl font-black text-gray-900">
                    Rs. {{ number_format($product->type === 'sell' ? $product->price : $product->starting_bid) }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <x-icon name="o-photo" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Images</p>
                <p class="text-2xl font-black text-gray-900">{{ $product->images->count() }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <x-icon name="o-clock" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Status</p>
                <p class="text-2xl font-black text-gray-900">{{ ucfirst($product->status) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                <x-icon name="o-shield-check" class="w-5 h-5" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Approval</p>
                <p class="text-2xl font-black {{ $product->is_approved ? 'text-green-600' : 'text-amber-500' }}">
                    {{ $product->is_approved ? 'Approved' : 'Pending' }}
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.5fr)_420px] gap-4">
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-wider text-gray-800">Product Gallery</p>
                            <p class="text-[11px] text-gray-500 truncate">{{ $product->name }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <x-badge :value="ucfirst($product->type)" :class="$product->type === 'auction' ? 'badge-info' : 'badge-success'" class="text-[10px] font-bold uppercase" />
                        <x-badge :value="$product->category?->name ?? 'Uncategorized'" class="badge-ghost text-[10px] font-bold uppercase" />
                    </div>
                </div>

                <div x-data="{ selectedImage: @js($product->image ? Storage::url($product->image) : null) }" class="p-4 space-y-4">
                    <div class="h-64 rounded-2xl border border-gray-200 bg-[#f5f2ea] flex items-center justify-center overflow-hidden">
                        <template x-if="selectedImage">
                            <img :src="selectedImage" alt="{{ $product->name }}" class="w-full h-full object-contain bg-white" />
                        </template>

                        <template x-if="!selectedImage">
                            <div class="flex flex-col items-center gap-2 text-gray-500">
                                <x-icon name="o-photo" class="w-12 h-12" />
                                <span class="text-xs font-semibold">No image selected</span>
                            </div>
                        </template>
                    </div>

                    @if($product->images->isNotEmpty())
                        <div class="flex flex-wrap gap-3">
                            @foreach($product->images as $image)
                                <button
                                    type="button"
                                    @click="selectedImage = '{{ Storage::url($image->path) }}'"
                                    x-bind:class="selectedImage === '{{ Storage::url($image->path) }}' ? 'border-[#2FA084] ring-1 ring-[#2FA084]' : 'border-gray-200'"
                                    class="w-11 h-11 rounded-xl border bg-[#f5f2ea] overflow-hidden"
                                >
                                    <img src="{{ Storage::url($image->path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover" />
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Product Information</p>
                </div>

                <div class="p-4 space-y-5">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Product Name</p>
                        <p class="mt-2 text-4xl leading-none font-black text-gray-900">{{ $product->name }}</p>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Description</p>
                        <div class="mt-3 rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-sm leading-7 text-gray-700">{{ $product->description }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Slug</p>
                            <p class="mt-2 text-sm font-black text-gray-900 break-all">{{ $product->slug }}</p>
                        </div>

                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Auction End</p>
                            <p class="mt-2 text-sm font-black text-gray-900">{{ $product->auction_end?->format('M d, Y h:i A') ?? 'Not applicable' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Seller Information</p>
                </div>

                <div class="p-4 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-black overflow-hidden">
                            @if($product->user?->avatar)
                                <img src="{{ Storage::url($product->user->avatar) }}" alt="{{ $product->user->name }}" class="w-full h-full object-cover" />
                            @else
                                {{ strtoupper(substr($product->user?->name ?? 'U', 0, 2)) }}
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-black text-gray-900 truncate">{{ $product->user?->name ?? 'Unknown Seller' }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $product->user?->email ?? 'No email available' }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4 space-y-3">
                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Username</p>
                            <p class="mt-2 text-sm font-black text-gray-900">{{ $product->user?->username ?? 'Not available' }}</p>
                        </div>

                        <div class="rounded-xl bg-[#f5f2ea] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Phone</p>
                            <p class="mt-2 text-sm font-black text-gray-900">{{ $product->user?->phone ?? 'Not available' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full bg-[#2FA084]"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-gray-800">Listing Metadata</p>
                </div>

                <div class="p-4 space-y-3">
                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Created At</p>
                        <p class="mt-2 text-sm font-black text-gray-900">{{ $product->created_at?->format('M d, Y h:i A') }}</p>
                    </div>

                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Updated At</p>
                        <p class="mt-2 text-sm font-black text-gray-900">{{ $product->updated_at?->format('M d, Y h:i A') }}</p>
                    </div>

                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Approval State</p>
                        <div class="mt-2 inline-flex items-center gap-2 text-xs font-bold {{ $product->is_approved ? 'text-green-600' : 'text-amber-600' }}">
                            <span class="w-2 h-2 rounded-full {{ $product->is_approved ? 'bg-green-500' : 'bg-amber-500' }}"></span>
                            {{ $product->is_approved ? 'Approved and live' : 'Pending review' }}
                        </div>
                    </div>

                    <div class="rounded-xl bg-[#f5f2ea] p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Category</p>
                        <p class="mt-2 text-sm font-black text-gray-900">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                    </div>
                </div>

                <div class="px-4 pb-4 pt-2">
                    <div class="border-t border-gray-200 pt-4 flex items-center gap-3">
                        @if(! $product->is_approved)
                            <x-button
                                label="Approve"
                                icon="o-check"
                                class="btn-outline flex-1 rounded-xl"
                                wire:click="approveProduct"
                                spinner="approveProduct"
                            />
                        @endif

                        <a href="{{ route('admin.products') }}" wire:navigate class="flex-1">
                            <x-button label="Back" icon="o-x-mark" class="btn-ghost w-full rounded-xl border border-gray-300" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
