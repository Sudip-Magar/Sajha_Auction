<div>
    <x-header title="FAQ Management" subtitle="Manage frequently asked questions and their categories" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Add FAQ" icon="o-plus" class="btn-primary shadow-lg shadow-primary/20" wire:click="openCreateModal" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-question-mark-circle" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total FAQs</p>
                <p class="text-2xl font-black text-gray-900">{{ $totalFaqs }}</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-check-circle" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active</p>
                <p class="text-2xl font-black text-gray-900">{{ $activeFaqs }}</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
                <x-icon name="o-tag" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Categories</p>
                <p class="text-2xl font-black text-gray-900">{{ $totalCategories }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-gray-50/30">
            <div class="flex items-center gap-2">
                <div class="w-1 bg-[#2FA084] h-6 rounded-full"></div>
                <h3 class="font-black text-gray-800 uppercase tracking-tighter">FAQ Inventory</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full xl:w-[34rem]">
                <x-input placeholder="Filter FAQs..." wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" class="bg-white" />
                <x-select wire:model.live="categoryFilter" :options="$categoryOptions" placeholder="All Categories" icon="o-tag" class="bg-white" />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'id', 'label' => 'ID', 'class' => 'w-16 text-gray-400'],
                ['key' => 'question', 'label' => 'Question'],
                ['key' => 'category.name', 'label' => 'Category'],
                ['key' => 'is_active', 'label' => 'Status'],
                ['key' => 'sort_order', 'label' => 'Rank'],
                ['key' => 'actions', 'label' => '', 'sortable' => false],
            ];
        @endphp

        <x-table :headers="$headers" :rows="$faqs" with-pagination class="!border-none">
            @scope('cell_question', $faq)
                <div class="min-w-0 max-w-md py-1">
                    <div class="font-black text-gray-900 truncate">{{ $faq->question }}</div>
                    <div class="text-xs text-gray-400 truncate mt-0.5">{{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 80) }}</div>
                </div>
            @endscope

            @scope('cell_category.name', $faq)
                <span class="text-xs font-black text-gray-500 uppercase tracking-tighter">{{ $faq->category?->name ?? 'Unassigned' }}</span>
            @endscope

            @scope('cell_is_active', $faq)
                <div class="flex items-center gap-2">
                    <div @class([
                        'w-2 h-2 rounded-full',
                        'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]' => $faq->is_active,
                        'bg-gray-300' => ! $faq->is_active,
                    ])></div>
                    <span @class([
                        'text-xs font-black uppercase tracking-tighter',
                        'text-green-600' => $faq->is_active,
                        'text-gray-400' => ! $faq->is_active,
                    ])>{{ $faq->is_active ? 'active' : 'inactive' }}</span>
                </div>
            @endscope

            @scope('cell_sort_order', $faq)
                <span class="font-mono font-bold text-gray-400 text-xs">#{{ $faq->sort_order }}</span>
            @endscope

            @scope('actions', $faq)
                <div class="flex items-center gap-1 justify-end">
                    <x-button icon="o-pencil" class="btn-sm btn-ghost hover:bg-blue-50 hover:text-blue-600 rounded-xl" wire:click="editFaq({{ $faq->id }})" />
                    <x-button icon="o-power" class="btn-sm btn-ghost rounded-xl {{ $faq->is_active ? 'hover:bg-red-50 hover:text-red-600 text-gray-300' : 'hover:bg-green-50 hover:text-green-600 text-green-400' }}" wire:click="toggleStatus({{ $faq->id }})" />
                    <x-button icon="o-trash" class="btn-sm btn-ghost hover:bg-red-50 hover:text-red-600 text-gray-300 rounded-xl" wire:confirm="Are you sure?" wire:click="deleteFaq({{ $faq->id }})" />
                </div>
            @endscope
        </x-table>
    </div>

    <x-modal wire:model="faqModal" title="{{ $editingFaq ? 'Edit FAQ' : 'Create New FAQ' }}" separator class="backdrop-blur-sm">
        <x-form wire:submit="saveFaq" class="space-y-6">
            <x-select label="Category" wire:model="faq_category_id" :options="$categoryOptions" placeholder="Select a category" icon="o-tag">
                <x-slot:append>
                    <x-button icon="o-plus" class="join-item btn-primary" tooltip="Add new category" wire:click="openCategoryQuickAdd" type="button" />
                </x-slot:append>
            </x-select>

            <x-input label="Question" wire:model="question" placeholder="e.g. How do I place a bid?" icon="o-question-mark-circle" />

            <x-textarea label="Answer" wire:model="answer" placeholder="Write the answer shown to users..." rows="5" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                <x-input label="Sort Order" wire:model="sort_order" type="number" icon="o-bars-arrow-down" />
                <x-toggle label="Visible to users" wire:model="is_active" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.faqModal = false" class="btn-ghost" />
                <x-button label="Confirm & Save" type="submit" class="btn-primary px-8" spinner="saveFaq" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <x-modal wire:model="categoryQuickAddModal" title="Add FAQ Category" separator class="backdrop-blur-sm">
        <x-form wire:submit="saveCategoryQuickAdd" class="space-y-6">
            <x-input label="Category Name" wire:model="newCategoryName" placeholder="e.g. Bidding & Auctions" icon="o-tag" />

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.categoryQuickAddModal = false" class="btn-ghost" />
                <x-button label="Add Category" type="submit" class="btn-primary px-8" spinner="saveCategoryQuickAdd" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
