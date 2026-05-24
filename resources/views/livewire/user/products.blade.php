<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <x-header title="My Products" subtitle="Manage your auction and sales items" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Upload Product" icon="o-plus" class="btn-primary shadow-lg shadow-primary/20" wire:click="openCreateModal" />
        </x-slot:actions>
    </x-header>

    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        @php
            $headers = [
                ['key' => 'id', 'label' => 'ID', 'class' => 'w-16'],
                ['key' => 'name', 'label' => 'Product'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'price_display', 'label' => 'Price/Start Bid'],
                ['key' => 'is_approved', 'label' => 'Approval'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'actions', 'label' => '', 'sortable' => false],
            ];
        @endphp

        <x-table :headers="$headers" :rows="$products" @class(['table-auto w-full'])>
            @scope('cell_name', $product)
                <div class="flex items-center gap-4 py-1">
                    <div class="w-12 h-12 rounded-xl overflow-hidden border border-gray-100 bg-gray-50 flex items-center justify-center shrink-0">
                        @if($product->image)
                            <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover" />
                        @else
                            <x-icon name="o-photo" class="w-6 h-6 text-gray-300" />
                        @endif
                    </div>
                    <div>
                        <div class="font-black text-gray-900">{{ $product->name }}</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $product->category->name ?? 'Uncategorized' }}</div>
                    </div>
                </div>
            @endscope

            @scope('cell_type', $product)
                <div class="flex flex-col gap-1">
                    <x-badge :value="ucfirst($product->type)" :class="$product->type === 'auction' ? 'badge-info text-white' : 'badge-primary text-white'" class="font-bold text-[10px] uppercase" />
                    @if($product->type === 'auction' && $product->auction_type)
                        <span class="text-[9px] font-black uppercase text-gray-400 tracking-tighter">{{ $product->auction_type }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_price_display', $product)
                <span class="font-bold text-gray-700">
                    @if($product->type === 'sell')
                        Rs. {{ number_format($product->sale_price) }}
                    @elseif($product->auction_type === 'penny')
                        Rs. {{ number_format($product->starting_price_cents / 100, 2) }} <span class="text-[10px] text-gray-400 font-medium">(Penny)</span>
                    @else
                        Rs. {{ number_format($product->starting_bid) }}
                    @endif
                </span>
            @endscope

            @scope('cell_is_approved', $product)
                <div class="flex items-center gap-2">
                    <div @class([
                        'w-2 h-2 rounded-full',
                        'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]' => $product->is_approved,
                        'bg-yellow-400 shadow-[0_0_8px_rgba(250,204,21,0.5)]' => !$product->is_approved,
                    ])></div>
                    <span @class([
                        'text-[10px] font-black uppercase tracking-tighter',
                        'text-green-600' => $product->is_approved,
                        'text-yellow-600' => !$product->is_approved,
                    ])>{{ $product->is_approved ? 'Approved' : 'Pending' }}</span>
                </div>
            @endscope

            @scope('cell_status', $product)
                <x-badge :value="ucfirst($product->status)" @class([
                    'font-bold text-[10px] uppercase',
                    'badge-success text-white' => $product->status === 'active',
                    'badge-warning text-white' => $product->status === 'pending',
                    'badge-ghost' => !in_array($product->status, ['active', 'pending']),
                ]) />
            @endscope

            @scope('actions', $product)
                <div class="flex items-center gap-1 justify-end">
                    <x-button label="Edit" icon="o-pencil-square" class="btn-sm btn-ghost" wire:click="editProduct({{ $product->id }})" />
                    @if(!$product->is_approved)
                        <x-button label="Delete" icon="o-trash" class="btn-sm btn-ghost text-red-500 hover:bg-red-50"
                            wire:click="deleteProduct({{ $product->id }})"
                            wire:confirm="Are you sure you want to delete this product? All of its images will also be permanently deleted from the system." />
                    @endif
                </div>
            @endscope
        </x-table>
    </div>

    {{-- Product Modal --}}
    <x-modal wire:model="productModal" title="" box-class="!w-full !max-w-[1400px] !p-0 overflow-hidden bg-[#F8FAFC]">

        <x-form wire:submit="saveProduct">

            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_380px] min-h-[600px]">

                {{-- LEFT CONTENT --}}
                <div class="overflow-y-auto p-6 space-y-6 max-h-[80vh]">

                    {{-- HERO --}}
                    <div class="rounded-3xl bg-gradient-to-r from-[#1F6F5F] to-[#2FA084] p-8 text-white">
                        <div class="flex items-start justify-between gap-6">
                            <div class="max-w-2xl">
                                <h2 class="text-3xl font-black leading-tight">
                                    {{ $editingProduct ? 'Update Product Listing' : 'Create New Listing' }}
                                </h2>
                                <p class="mt-3 text-sm leading-6 text-white/80">
                                    Add product details, pricing, auction setup,
                                    and upload images before submitting for approval.
                                </p>
                            </div>
                            <div class="hidden md:flex h-20 w-20 items-center justify-center rounded-3xl bg-white/10 backdrop-blur">
                                <x-icon name="o-shopping-bag" class="w-10 h-10" />
                            </div>
                        </div>
                    </div>

                    {{-- PRODUCT INFO --}}
                    <section class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-6">
                            <p class="text-xs font-black uppercase tracking-widest text-[#2FA084]">Product Information</p>
                            <h3 class="mt-2 text-2xl font-black text-gray-900">Basic Details</h3>
                            <p class="mt-1 text-sm text-gray-500">Information buyers will see first.</p>
                        </div>

                        <div class="space-y-5">

                            <x-input
                                label="Product Name"
                                wire:model="name"
                                placeholder="Vintage Rolex Watch"
                                icon="o-pencil-square"
                                class="input-bordered"
                            />

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                <x-select
                                    label="Category"
                                    wire:model="category_id"
                                    :options="$categories"
                                    placeholder="Select Category"
                                    icon="o-tag"
                                    class="select-bordered"
                                />

                                <x-select
                                    label="Sale Type"
                                    wire:model.live="type"
                                    :options="[
                                        ['id' => 'sell', 'name' => 'Direct Sell'],
                                        ['id' => 'auction', 'name' => 'Auction']
                                    ]"
                                    icon="o-shopping-bag"
                                    class="select-bordered"
                                />

                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                <x-select
                                    label="Condition"
                                    wire:model="condition"
                                    :options="[
                                        ['id' => 'new', 'name' => 'Brand New'],
                                        ['id' => 'like-new', 'name' => 'Like New'],
                                        ['id' => 'used', 'name' => 'Used']
                                    ]"
                                    icon="o-sparkles"
                                    class="select-bordered"
                                />

                                <x-input
                                    label="Retail Price (Rs.)"
                                    wire:model="retail_price"
                                    type="number"
                                    placeholder="Original market price"
                                    icon="o-banknotes"
                                    class="input-bordered"
                                />

                            </div>

                        </div>
                    </section>

                    {{-- PRICING / AUCTION --}}
                    <section class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-6">
                            <p class="text-xs font-black uppercase tracking-widest text-sky-600">
                                {{ $type === 'auction' ? 'Auction Setup' : 'Pricing & Inventory' }}
                            </p>
                            <h3 class="mt-2 text-2xl font-black text-gray-900">
                                {{ $type === 'auction' ? 'Auction Configuration' : 'Direct Sale Setup' }}
                            </h3>
                        </div>

                        @if($type === 'sell')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                <x-input
                                    label="Sale Price (Rs.)"
                                    wire:model="sale_price"
                                    type="number"
                                    icon="o-banknotes"
                                    class="input-bordered"
                                />

                                <x-input
                                    label="Stock Quantity"
                                    wire:model="stock_quantity"
                                    type="number"
                                    icon="o-archive-box"
                                    class="input-bordered"
                                />

                            </div>

                        @endif

                        @if($type === 'auction')

                            <div class="space-y-5">

                                <x-select
                                    label="Auction Type"
                                    wire:model.live="auction_type"
                                    :options="[
                                        ['id' => 'traditional', 'name' => 'Traditional Auction'],
                                        ['id' => 'penny', 'name' => 'Penny Auction']
                                    ]"
                                    icon="o-ticket"
                                    class="select-bordered"
                                />

                                @if($auction_type === 'traditional')

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                        <x-input
                                            label="Starting Bid (Rs.)"
                                            wire:model="starting_bid"
                                            type="number"
                                            icon="o-banknotes"
                                            class="input-bordered"
                                        />

                                        <x-input
                                            label="Auction End Date"
                                            wire:model.live="auction_end_date_en"
                                            type="date"
                                            icon="o-calendar"
                                            class="input-bordered"
                                        />

                                        <x-input
                                            label="Auction End Time"
                                            wire:model.live="auction_end_time"
                                            type="time"
                                            icon="o-clock"
                                            class="input-bordered"
                                        />

                                        <x-input
                                            label="Auction End Date (Nepali)"
                                            wire:model="auction_end_np"
                                            placeholder="e.g. 2081-02-05"
                                            icon="o-calendar-days"
                                            class="input-bordered"
                                            hint="Optional – Bikram Sambat date"
                                        />

                                    </div>

                                @endif

                                @if($auction_type === 'penny')

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                        <x-input
                                            label="Starting Price (Cents)"
                                            wire:model="starting_price_cents"
                                            type="number"
                                            class="input-bordered"
                                        />

                                        <x-input
                                            label="Bid Increment (Cents)"
                                            wire:model="bid_increment_cents"
                                            type="number"
                                            class="input-bordered"
                                        />

                                        <x-input
                                            label="Initial Timer (seconds)"
                                            wire:model="timer_seconds"
                                            type="number"
                                            class="input-bordered"
                                        />

                                        <x-input
                                            label="Timer Extension (seconds)"
                                            wire:model="timer_extension_seconds"
                                            type="number"
                                            class="input-bordered"
                                        />

                                    </div>

                                @endif

                                {{-- SCHEDULE START (optional) --}}
                                <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 p-5">
                                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">
                                        Schedule Auction Start <span class="normal-case font-normal text-gray-400">(Optional)</span>
                                    </p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                        <x-input
                                            label="Start Date"
                                            wire:model.live="scheduled_for_date_en"
                                            type="date"
                                            icon="o-calendar"
                                            class="input-bordered"
                                        />

                                        <x-input
                                            label="Start Time"
                                            wire:model.live="scheduled_for_time"
                                            type="time"
                                            icon="o-clock"
                                            class="input-bordered"
                                        />

                                        <x-input
                                            label="Start Date (Nepali)"
                                            wire:model="scheduled_for_np"
                                            placeholder="e.g. 2081-02-05"
                                            icon="o-calendar-days"
                                            class="input-bordered"
                                            hint="Optional – Bikram Sambat date"
                                        />

                                    </div>
                                </div>

                            </div>

                        @endif

                    </section>

                    {{-- DESCRIPTION --}}
                    <section class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-6">
                            <p class="text-xs font-black uppercase tracking-widest text-[#2FA084]">Description</p>
                            <h3 class="mt-2 text-2xl font-black text-gray-900">Product Details</h3>
                        </div>

                        <div class="space-y-5">

                            <x-textarea
                                label="Description"
                                wire:model="description"
                                rows="6"
                                placeholder="Describe the product condition, features, and highlights..."
                                class="textarea-bordered"
                            />

                            <x-textarea
                                label="Specifications"
                                wire:model="specifications"
                                rows="5"
                                placeholder="Brand, model, dimensions, material..."
                                class="textarea-bordered"
                            />

                        </div>
                    </section>

                </div>

                {{-- RIGHT SIDEBAR --}}
                <aside class="overflow-y-auto border-l border-gray-200 bg-white p-6 max-h-[80vh]">

                    <div class="space-y-6">

                        {{-- STATUS SUMMARY --}}
                        <div class="rounded-3xl bg-[#F8FAFC] border border-gray-100 p-5">
                            <div class="grid grid-cols-3 gap-4 text-center">

                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Type</p>
                                    <p class="mt-2 text-sm font-black text-[#1F6F5F]">{{ ucfirst($type) }}</p>
                                </div>

                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Images</p>
                                    <p class="mt-2 text-sm font-black text-gray-900">{{ count($existingImages) + count($newImages) }}</p>
                                </div>

                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Review</p>
                                    <p class="mt-2 text-sm font-black text-amber-500">Pending</p>
                                </div>

                            </div>
                        </div>

                        {{-- IMAGE UPLOAD --}}
                        <section class="rounded-3xl border border-gray-100 p-5">

                            <div class="mb-5">
                                <h3 class="text-xl font-black text-gray-900">Product Images</h3>
                                <p class="mt-1 text-sm text-gray-500">Upload clear and high-quality photos.</p>
                            </div>

                            <label
                                for="product-images-upload"
                                class="relative flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-gray-200 bg-gray-50 p-10 text-center hover:border-[#2FA084] transition-all duration-200 cursor-pointer"
                            >
                                <input
                                    id="product-images-upload"
                                    type="file"
                                    wire:model="newImages"
                                    accept="image/*"
                                    multiple
                                    class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
                                />

                                <div class="space-y-4 pointer-events-none">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm">
                                        <x-icon name="o-camera" class="w-8 h-8 text-[#1F6F5F]" />
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900">Upload Images</p>
                                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, WEBP up to 2MB</p>
                                    </div>
                                </div>
                            </label>

                            @error('newImages')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror

                            @if($existingImages || $newImages)
                                <div class="grid grid-cols-2 gap-3 mt-5">

                                    @foreach($existingImages as $index => $image)
                                        <div class="relative aspect-square overflow-hidden rounded-2xl border border-gray-100">
                                            <img
                                                src="{{ Storage::url($image['path']) }}"
                                                class="h-full w-full object-cover"
                                            />
                                            <button
                                                type="button"
                                                wire:click="removeExistingImage({{ $image['id'] }})"
                                                class="absolute top-2 right-2 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white shadow-lg"
                                            >
                                                <x-icon name="o-trash" class="w-4 h-4" />
                                            </button>
                                        </div>
                                    @endforeach

                                    @foreach($newImages as $index => $image)
                                        <div class="relative aspect-square overflow-hidden rounded-2xl border border-gray-100">
                                            <img
                                                src="{{ $image->temporaryUrl() }}"
                                                class="h-full w-full object-cover"
                                            />
                                            <button
                                                type="button"
                                                wire:click="removeNewImage({{ $index }})"
                                                class="absolute top-2 right-2 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white shadow-lg"
                                            >
                                                <x-icon name="o-trash" class="w-4 h-4" />
                                            </button>
                                        </div>
                                    @endforeach

                                </div>
                            @endif

                        </section>

                        {{-- ACTIONS --}}
                        <div class="sticky bottom-0 bg-white pt-4 border-t border-gray-100">
                            <div class="flex gap-3">

                                <x-button
                                    label="Cancel"
                                    @click="$wire.productModal = false"
                                    class="btn-ghost flex-1"
                                />

                                <x-button
                                    :label="$editingProduct ? 'Update Product' : 'Submit Product'"
                                    type="submit"
                                    class="btn-primary flex-1 bg-[#1F6F5F] border-none"
                                    spinner="saveProduct"
                                />

                            </div>
                        </div>

                    </div>

                </aside>

            </div>

        </x-form>

    </x-modal>

</div>
