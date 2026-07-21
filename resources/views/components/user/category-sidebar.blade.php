@props([
    'categories',
    'activeCategory' => '',
    'postRoute' => null,
])

@php
    $activeCategory = (string) $activeCategory;
    $categoryUrl = fn ($category) => route('user.search.product', ['category' => $category->id]);
    $isActive = fn ($category) => $activeCategory !== '' && (string) $category->id === $activeCategory;
    $hasActiveChild = fn ($category) => $category->children->contains(fn ($childCategory) => $isActive($childCategory));
@endphp

<aside {{ $attributes->merge(['class' => 'hidden overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-800 dark:bg-[#181A1F] lg:block']) }}>
    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
        <h2 class="ui-heading flex items-center gap-2 font-black">
            <x-icon name="o-squares-2x2" class="ui-icon-lg text-[#0C8FE8]" />
            All Categories
        </h2>
        <span class="ui-small font-bold text-gray-500">{{ $categories->count() }}</span>
    </div>

    @if($postRoute)
        <a href="{{ $postRoute }}" wire:navigate class="flex items-center gap-3 border-b border-gray-200 bg-sky-50 px-4 py-3 dark:border-gray-800 dark:bg-sky-950/30">
            <div class="flex h-8 w-8 items-center justify-center rounded-md bg-white text-[#0C8FE8] shadow-sm dark:bg-[#202228]">
                <x-icon name="o-bolt" class="ui-icon" />
            </div>
            <div class="min-w-0">
                <p class="truncate font-black">Boost your listing</p>
                <p class="truncate ui-small font-semibold text-gray-500">Reach buyers faster</p>
            </div>
            <x-icon name="o-chevron-right" class="ui-icon ml-auto text-gray-500" />
        </a>
    @endif

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse($categories as $category)
            <div x-data="{ open: {{ $hasActiveChild($category) ? 'true' : 'false' }} }">
                @if($category->children->isNotEmpty())
                    <button
                        type="button"
                        @click="open = ! open"
                        @class([
                            'flex w-full items-center justify-between px-4 py-3 text-left font-bold hover:bg-gray-50 dark:hover:bg-gray-800/70',
                            'bg-sky-50 text-[#0C8FE8] dark:bg-sky-950/30' => $hasActiveChild($category),
                        ])
                    >
                        <span class="flex min-w-0 items-center gap-3">
                            <span @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-md',
                                'bg-[#0C8FE8] text-white' => $hasActiveChild($category),
                                'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' => ! $hasActiveChild($category),
                            ])>
                                <x-icon name="o-folder" class="ui-icon" />
                            </span>
                            <span class="truncate">{{ $category->name }}</span>
                        </span>
                        <span class="rounded-md p-1 text-gray-400 hover:bg-white/70 dark:hover:bg-gray-800" aria-label="Toggle {{ $category->name }} subcategories">
                            <x-icon name="o-chevron-right" class="ui-icon shrink-0 transition-transform" ::class="open ? 'rotate-90' : ''" />
                        </span>
                    </button>
                    <div x-show="open" x-transition class="border-t border-gray-100 bg-gray-50/70 dark:border-gray-800 dark:bg-[#101114]" style="{{ $hasActiveChild($category) ? '' : 'display: none;' }}">
                        @foreach($category->children as $childCategory)
                            <a
                                href="{{ $categoryUrl($childCategory) }}"
                                wire:navigate
                                @class([
                                    'flex items-center gap-3 px-4 py-2.5 pl-12 font-semibold text-gray-600 hover:text-[#0C8FE8] dark:text-gray-300',
                                    'bg-sky-100 text-[#0C8FE8] dark:bg-sky-950/50' => $isActive($childCategory),
                                ])
                            >
                                <x-icon name="o-tag" class="ui-icon shrink-0" />
                                <span class="truncate">{{ $childCategory->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div
                        @class([
                            'flex items-center justify-between px-4 py-3 font-bold text-gray-500 dark:text-gray-400',
                        ])
                    >
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                <x-icon name="o-folder" class="ui-icon" />
                            </span>
                            <span class="truncate">{{ $category->name }}</span>
                        </span>
                    </div>
                @endif
            </div>
        @empty
            <div class="px-4 py-8 font-semibold text-gray-500">No categories available.</div>
        @endforelse
    </div>
</aside>
