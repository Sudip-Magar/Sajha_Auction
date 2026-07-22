<div>
    <x-header title="Sub-Category Management" subtitle="Manage auction sub-categories under parent categories" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Add Sub-Category" icon="o-plus" class="btn-primary shadow-lg shadow-primary/20" wire:click="openCreateModal" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-rectangle-stack" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Sub-Categories</p>
                <p class="text-2xl font-black text-gray-900">{{ $totalSubCategories }}</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-check-circle" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active</p>
                <p class="text-2xl font-black text-gray-900">{{ $activeSubCategories }}</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-cube" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Used By Products</p>
                <p class="text-2xl font-black text-gray-900">{{ $linkedProducts }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-gray-50/30">
            <div class="flex items-center gap-2">
                <div class="w-1 bg-[#2FA084] h-6 rounded-full"></div>
                <h3 class="font-black text-gray-800 uppercase tracking-tighter">Sub-Category Inventory</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full xl:w-[34rem]">
                <x-input placeholder="Filter sub-categories..." wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" class="bg-white" />
                <x-select wire:model.live="categoryFilter" :options="$categoryOptions" placeholder="All Categories" icon="o-tag" class="bg-white" />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'id', 'label' => 'ID', 'class' => 'w-16 text-gray-400'],
                ['key' => 'name', 'label' => 'Sub-Category Information'],
                ['key' => 'category.name', 'label' => 'Parent Category'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'products_count', 'label' => 'Products'],
                ['key' => 'sort_order', 'label' => 'Rank'],
                ['key' => 'actions', 'label' => '', 'sortable' => false],
            ];
        @endphp

        <x-table :headers="$headers" :rows="$subCategories" with-pagination class="!border-none">
            @scope('cell_name', $subCategory)
                <div class="flex items-center gap-4 py-1">
                    <div class="w-12 h-12 rounded-2xl overflow-hidden border border-gray-100 shadow-inner bg-gray-50 flex items-center justify-center shrink-0">
                        @if($subCategory->image)
                            <img src="{{ Storage::url($subCategory->image) }}" class="w-full h-full object-cover" />
                        @else
                            <x-icon name="{{ $subCategory->icon ?: 'o-rectangle-stack' }}" class="w-6 h-6 text-gray-300" />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="font-black text-gray-900 truncate">{{ $subCategory->name }}</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">{{ $subCategory->slug }}</div>
                    </div>
                </div>
            @endscope

            @scope('cell_category.name', $subCategory)
                <span class="text-xs font-black text-gray-500 uppercase tracking-tighter">{{ $subCategory->category?->name ?? 'Unassigned' }}</span>
            @endscope

            @scope('cell_status', $subCategory)
                <div class="flex items-center gap-2">
                    <div @class([
                        'w-2 h-2 rounded-full',
                        'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]' => $subCategory->status === 'active',
                        'bg-gray-300' => $subCategory->status !== 'active',
                    ])></div>
                    <span @class([
                        'text-xs font-black uppercase tracking-tighter',
                        'text-green-600' => $subCategory->status === 'active',
                        'text-gray-400' => $subCategory->status !== 'active',
                    ])>{{ $subCategory->status }}</span>
                </div>
            @endscope

            @scope('cell_products_count', $subCategory)
                <span class="font-mono font-bold text-gray-500 text-xs">{{ $subCategory->products_count }}</span>
            @endscope

            @scope('cell_sort_order', $subCategory)
                <span class="font-mono font-bold text-gray-400 text-xs">#{{ $subCategory->sort_order }}</span>
            @endscope

            @scope('actions', $subCategory)
                <div class="flex items-center gap-1 justify-end">
                    <x-button icon="o-pencil" class="btn-sm btn-ghost hover:bg-blue-50 hover:text-blue-600 rounded-xl" wire:click="editSubCategory({{ $subCategory->id }})" />
                    <x-button icon="o-power" class="btn-sm btn-ghost rounded-xl {{ $subCategory->status === 'active' ? 'hover:bg-red-50 hover:text-red-600 text-gray-300' : 'hover:bg-green-50 hover:text-green-600 text-green-400' }}" wire:click="toggleStatus({{ $subCategory->id }})" />
                    <x-button icon="o-trash" class="btn-sm btn-ghost hover:bg-red-50 hover:text-red-600 text-gray-300 rounded-xl" wire:confirm="Are you sure?" wire:click="deleteSubCategory({{ $subCategory->id }})" />
                </div>
            @endscope
        </x-table>
    </div>

    <x-modal wire:model="subCategoryModal" title="{{ $editingSubCategory ? 'Edit Sub-Category' : 'Create New Sub-Category' }}" separator class="backdrop-blur-sm">
        <x-form wire:submit="saveSubCategory" class="space-y-6">
            <x-select label="Parent Category" wire:model="category_id" :options="$categoryOptions" placeholder="Select parent category" icon="o-tag" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input label="Sub-Category Name" wire:model.live="name" placeholder="e.g. Mobile Phones" icon="o-pencil-square" />
                <x-input label="URL Slug" wire:model="slug" placeholder="auto-generated" readonly icon="o-link" class="bg-gray-50" />
            </div>

            <x-textarea label="Description" wire:model="description" placeholder="Brief overview of this sub-category..." rows="3" />

            <x-input label="Icon Name" wire:model="icon" placeholder="o-device-phone-mobile" icon="o-face-smile" hint="Use HeroIcons names" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input label="Theme Color" wire:model="color" type="color" class="h-12" />
                <x-input label="Sort Order" wire:model="sort_order" type="number" icon="o-bars-arrow-down" />
            </div>

            <div class="divider text-[10px] font-black uppercase text-gray-400 tracking-widest">Media & Status</div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                <x-select label="Visibility Status" wire:model="status" :options="[['id' => 'active', 'name' => 'Active / Visible'], ['id' => 'inactive', 'name' => 'Hidden / Disabled']]" icon="o-eye" />
                <x-file label="Sub-Category Banner/Thumbnail" wire:model="image" accept="image/*" />

                @if($image || ($editingSubCategory && $editingSubCategory->image))
                    <div class="relative w-full h-32 rounded-2xl overflow-hidden border-2 border-dashed border-gray-200 p-1 group">
                        <img src="{{ $image ? $image->temporaryUrl() : Storage::url($editingSubCategory->image) }}" class="w-full h-full object-cover rounded-xl shadow-inner" />
                        <button type="button" wire:click="removeImage" class="absolute top-2 right-2 bg-red-500 text-white p-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                            <x-icon name="o-trash" class="w-4 h-4" />
                        </button>
                    </div>
                @endif
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.subCategoryModal = false" class="btn-ghost" />
                <x-button label="Confirm & Save" type="submit" class="btn-primary px-8" spinner="saveSubCategory" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
