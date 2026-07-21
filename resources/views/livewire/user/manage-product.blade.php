<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <x-header :title="$product ? 'Update Product' : 'Upload Product'" subtitle="Provide details about your product and auction setup" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Back to Products" icon="o-arrow-left" class="btn-ghost" link="{{ route('user.products') }}" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Left Side: Main Details --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Product Information --}}
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                        <x-icon name="o-information-circle" class="w-6 h-6 text-primary" />
                        Basic Information
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-input label="Product Name" wire:model="name" placeholder="e.g. Vintage Leather Jacket" icon="o-tag" />
                        </div>

                        <x-select label="Category" wire:model="sub_category_id" :options="$subCategories" placeholder="Select Category" icon="o-squares-2x2" />

                        <x-select label="Condition" wire:model="condition" :options="[
                            ['id' => 'new', 'name' => 'Brand New'],
                            ['id' => 'like-new', 'name' => 'Like New'],
                            ['id' => 'used', 'name' => 'Used']
                        ]" icon="o-sparkles" />

                        <x-input label="Retail Price (Rs.)" wire:model="retail_price" type="number" step="0.01" icon="o-banknotes" hint="MSRP / Original Price" />

                        @if($listing_type === 'direct_seller')
                            <x-input label="Sale Price (Rs.)" wire:model="sale_price" type="number" step="0.01" icon="o-currency-dollar" />
                        @endif

                        <x-select label="Price Type" wire:model="negotiable" :options="$negotiabilityOptions" icon="o-adjustments-horizontal" />

                        <x-input label="Quantity" wire:model="quantity" type="number" icon="o-archive-box" />

                        <x-input label="Location" wire:model="location" placeholder="e.g. Kathmandu, Nepal" icon="o-map-pin" />

                        <x-checkbox label="Delivery Available" wire:model="delivery_available" />
                    </div>

                    <div class="mt-6 space-y-4">
                        <x-textarea label="Description" wire:model="description" rows="4" placeholder="Describe the product details, history, and features..." />
                        <x-textarea label="Specifications" wire:model="specifications" rows="3" placeholder="Size, Material, Brand, etc." />
                    </div>
                </div>

                {{-- Listing Type & Auction Configuration --}}
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                        <x-icon name="o-shopping-cart" class="w-6 h-6 text-primary" />
                        Listing Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <x-select label="Listing Type" wire:model.live="listing_type" :options="[
                            ['id' => 'direct_seller', 'name' => 'Direct Sell'],
                            ['id' => 'auction', 'name' => 'Auction']
                        ]" icon="o-rocket-launch" />

                        @if($listing_type === 'auction')
                            <x-select label="Auction Type" wire:model.live="auction_type" :options="[
                                ['id' => 'traditional', 'name' => 'Traditional Auction']
                            ]" icon="o-ticket" />
                        @endif
                    </div>

                    @if($listing_type === 'auction')
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Auction Start --}}
                                <div>
                                    <label class="label font-bold text-sm">Auction Start Date (B.S.)</label>
                                    <input
                                        type="text"
                                        id="auction_start_np"
                                        wire:model="auction_start_np"
                                        data-nepali-date="auction-start"
                                        class="input input-bordered w-full"
                                        placeholder="YYYY-MM-DD"
                                        autocomplete="off"
                                    />
                                    <input type="hidden" wire:model="auction_start_date_en" data-english-date="auction-start">
                                    @error('auction_start_date_en') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <x-input label="Start Time" wire:model="auction_start_time" type="time" icon="o-clock" />

                                {{-- Traditional Specifics --}}
                                <div>
                                    <label class="label font-bold text-sm">Auction End Date (B.S.)</label>
                                    <input
                                        type="text"
                                        id="auction_end_np"
                                        wire:model="auction_end_np"
                                        data-nepali-date="auction-end"
                                        class="input input-bordered w-full"
                                        placeholder="YYYY-MM-DD"
                                        autocomplete="off"
                                    />
                                    <input type="hidden" wire:model="auction_end_date_en" data-english-date="auction-end">
                                    @error('auction_end_date_en') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <x-input label="End Time" wire:model="auction_end_time" type="time" icon="o-clock" />

                                <x-input label="Starting Bid (Rs.)" wire:model="starting_bid" type="number" step="0.01" icon="o-banknotes" />
                                <x-input label="Reserve Price (Rs.)" wire:model="reserve_price" type="number" step="0.01" icon="o-shield-check" hint="Optional" />
                                <x-input label="Min Bid Increment (Rs.)" wire:model="min_bid_increment" type="number" step="0.01" icon="o-plus-circle" />
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Side: Images & Actions --}}
            <div class="space-y-6">

                {{-- Image Upload --}}
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                        <x-icon name="o-camera" class="w-6 h-6 text-primary" />
                        Product Images
                    </h2>

                    <div class="space-y-4">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-2xl cursor-pointer hover:bg-gray-50 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <x-icon name="o-cloud-arrow-up" class="w-8 h-8 text-gray-400 mb-2" />
                                <p class="text-sm text-gray-500">Click to upload images</p>
                            </div>
                            <input type="file" wire:model="newImages" class="hidden" multiple accept="image/*" />
                        </label>
                        @error('newImages') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                        <div class="grid grid-cols-2 gap-3 mt-4">
                            {{-- Existing Images --}}
                            @foreach($existingImages as $image)
                                <div class="relative group aspect-square rounded-xl overflow-hidden border border-gray-100">
                                    <img src="{{ Storage::url($image['path']) }}" class="w-full h-full object-cover" />
                                    <button type="button" wire:click="removeExistingImage({{ $image['id'] }})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                        <x-icon name="o-x-mark" class="w-4 h-4" />
                                    </button>
                                </div>
                            @endforeach

                            {{-- New Images --}}
                            @foreach($newImages as $index => $image)
                                <div class="relative group aspect-square rounded-xl overflow-hidden border border-gray-100">
                                    <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover" />
                                    <button type="button" wire:click="removeNewImage({{ $index }})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                        <x-icon name="o-x-mark" class="w-4 h-4" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Action Card --}}
                <div class="bg-[#1F6F5F] rounded-3xl p-6 shadow-xl text-white">
                    <h3 class="font-black text-lg mb-2">Ready to list?</h3>
                    <p class="text-white/80 text-sm mb-6">Ensure all details are accurate. Once approved by an admin, you won't be able to edit or delete this product.</p>

                    <div class="space-y-3">
                        <x-button :label="$product ? 'Update Listing' : 'Submit for Approval'" type="submit" class="btn-primary w-full bg-white text-[#1F6F5F] border-none hover:bg-gray-100" spinner="save" />
                        <x-button label="Cancel" class="btn-ghost w-full text-white hover:bg-white/10" link="{{ route('user.products') }}" />
                    </div>
                </div>

            </div>
        </div>
    </x-form>

    {{-- Initialize Nepali Datepickers --}}
    <script>
        document.addEventListener('livewire:navigated', () => {
            if (window.initializeNepaliDatePickers) {
                window.initializeNepaliDatePickers();
            }
        });
    </script>
</div>
