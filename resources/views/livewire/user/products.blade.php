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

        <x-table :headers="$headers" :rows="$products" with-pagination>
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
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $product->category->name }}</div>
                    </div>
                </div>
            @endscope

            @scope('cell_type', $product)
                <x-badge :value="ucfirst($product->type)" :class="$product->type === 'auction' ? 'badge-info' : 'badge-primary'" />
            @endscope

            @scope('cell_price_display', $product)
                <span class="font-bold text-gray-700">
                    {{ $product->type === 'sell' ? 'Rs. ' . number_format($product->price) : 'Rs. ' . number_format($product->starting_bid) }}
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

            @scope('actions', $product)
                <x-button label="Edit" icon="o-pencil-square" class="btn-sm btn-ghost" wire:click="editProduct({{ $product->id }})" />
            @endscope
        </x-table>
    </div>

    <!-- Product Modal -->
    <x-modal wire:model="productModal" :title="$editingProduct ? 'Edit Product' : 'Upload New Product'" separator class="backdrop-blur-sm">
        <x-form wire:submit="saveProduct" class="space-y-6">
            <x-input label="Product Name" wire:model="name" placeholder="e.g. Vintage Rolex Watch" icon="o-pencil-square" />
            
            <x-textarea label="Description" wire:model="description" placeholder="Describe your product in detail..." rows="4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-select label="Category" wire:model="category_id" :options="$categories" placeholder="Select Category" icon="o-tag" />
                <x-select label="Sale Type" wire:model.live="type" :options="[['id' => 'sell', 'name' => 'Direct Sell'], ['id' => 'auction', 'name' => 'Auction']]" icon="o-shopping-bag" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($type === 'sell')
                    <x-input label="Direct Sell Price (Rs.)" wire:model="price" type="number" icon="o-banknotes" />
                @else
                    <x-input label="Starting Bid (Rs.)" wire:model="starting_bid" type="number" icon="o-currency-dollar" />
                    <x-input label="Auction End Time" wire:model="auction_end" type="datetime-local" icon="o-clock" />
                @endif
            </div>

            <x-file label="Product Images" wire:model="newImages" accept="image/*" multiple />

            @error('newImages')
                <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror

            @if($existingImages || $newImages)
                <div class="space-y-3">
                    <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Image Preview</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($existingImages as $image)
                            <div class="relative h-32 rounded-2xl overflow-hidden border border-gray-200 bg-gray-50">
                                <img src="{{ Storage::url($image['path']) }}" class="w-full h-full object-cover" />
                                <button type="button" class="absolute top-2 right-2 bg-white/90 text-red-500 rounded-full px-2 py-1 text-xs font-bold shadow" wire:click="removeExistingImage({{ $image['id'] }})">
                                    Remove
                                </button>
                            </div>
                        @endforeach

                        @foreach($newImages as $index => $image)
                            <div class="relative h-32 rounded-2xl overflow-hidden border border-dashed border-gray-200 bg-gray-50">
                                <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover" />
                                <button type="button" class="absolute top-2 right-2 bg-white/90 text-red-500 rounded-full px-2 py-1 text-xs font-bold shadow" wire:click="removeNewImage({{ $index }})">
                                    Remove
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400">The first remaining image will be used as the product thumbnail.</p>
                </div>
            @endif

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.productModal = false" class="btn-ghost" />
                <x-button :label="$editingProduct ? 'Update Product' : 'Submit for Approval'" type="submit" class="btn-primary px-8 shadow-lg shadow-primary/20" spinner="saveProduct" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
