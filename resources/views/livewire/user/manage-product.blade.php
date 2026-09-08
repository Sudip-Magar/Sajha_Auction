<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <x-header :title="$product ? 'Update Product' : 'Upload Product'" subtitle="Provide details about your product, condition, meetup place and listing setup" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Back to Products" icon="o-arrow-left" class="btn-ghost" link="{{ route('user.products') }}" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Left Side: Main Details --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Listing Type & Auction Configuration (At Top) --}}
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 dark:bg-[#181A1F] dark:border-gray-800 dark:shadow-none">
                    <h2 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-2 dark:text-gray-100">
                        <x-icon name="o-shopping-cart" class="w-6 h-6 text-primary" />
                        Listing Details & Sale Type
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        @if($isAuctionAllowed)
                            <x-select label="Listing Type *" wire:model.live="listing_type" :options="[
                                ['id' => 'direct_seller', 'name' => 'Direct Sell (Second-Hand Marketplace)'],
                                ['id' => 'auction', 'name' => 'Auction']
                            ]" icon="o-rocket-launch" />
                        @else
                            <div>
                                <x-select label="Listing Type *" wire:model.live="listing_type" :options="[
                                    ['id' => 'direct_seller', 'name' => 'Direct Sell (Second-Hand Marketplace)']
                                ]" icon="o-rocket-launch" />
                                <p class="text-xs text-amber-700 bg-amber-50 p-2.5 rounded-xl border border-amber-200 mt-2 flex items-center gap-2 font-medium dark:text-amber-300 dark:bg-amber-950/30 dark:border-amber-900/60">
                                    <x-icon name="o-information-circle" class="w-4 h-4 text-amber-600 shrink-0" />
                                    <span>Auction listing requires auction approval. Submit your request under <a href="{{ route('user.join-auction') }}" wire:navigate class="underline font-bold">Join Auction</a> to enable live auctions.</span>
                                </p>
                            </div>
                        @endif

                        @if($listing_type === 'auction' && $isAuctionAllowed)
                            <x-select label="Auction Type *" wire:model.live="auction_type" :options="[
                                ['id' => 'traditional', 'name' => 'Traditional Auction']
                            ]" icon="o-ticket" />
                        @endif
                    </div>

                    @if($listing_type === 'auction' && $isAuctionAllowed)
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 space-y-6 dark:bg-gray-800/60 dark:border-gray-800">
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

                                <div>
                                    <x-input label="Starting Bid (Rs.) *" wire:model.live="starting_bid" type="number" step="0.01" icon="o-banknotes" hint="Actual starting price used by the auction" />
                                    @if($this->estimatedValue)
                                        <div class="mt-2 p-2.5 bg-violet-50 rounded-xl border border-violet-200 flex items-center justify-between gap-2 dark:bg-violet-950/30 dark:border-violet-900/60">
                                            <div class="text-[11px] text-violet-900 font-medium dark:text-violet-200">
                                                <span class="font-bold">Estimated Value:</span> Rs. {{ number_format($this->estimatedValue, 2) }}
                                                <br>
                                                <span class="font-bold">Suggested Starting Price (80%):</span> Rs. {{ number_format($this->suggestedStartingPrice, 2) }}
                                            </div>
                                            <button type="button" wire:click="applySuggestedStartingPrice" class="text-[11px] font-black text-violet-700 hover:text-violet-900 underline shrink-0 dark:text-violet-300 dark:hover:text-violet-100">
                                                Apply
                                            </button>
                                        </div>
                                    @else
                                        <p class="text-[11px] text-gray-400 mt-2 dark:text-gray-500">Add a retail price, purchase date and condition above to see an estimated value.</p>
                                    @endif
                                </div>
                                <div>
                                    <x-input label="Reserve Price (Rs.)" wire:model="reserve_price" type="number" step="0.01" icon="o-shield-check" hint="Minimum acceptable price (Algorithm 1)" />
                                    @if($this->recommendedReserve)
                                        <div class="mt-2 p-2.5 bg-emerald-50 rounded-xl border border-emerald-200 flex items-center justify-between dark:bg-emerald-950/30 dark:border-emerald-900/60">
                                            <div class="text-[11px] text-emerald-900 font-medium dark:text-emerald-200">
                                                <span class="font-bold">Algorithm 3 Myerson Recommendation:</span> Rs. {{ number_format($this->recommendedReserve, 2) }}
                                            </div>
                                            <button type="button" wire:click="applyRecommendedReserve" class="text-[11px] font-black text-emerald-700 hover:text-emerald-900 underline dark:text-emerald-300 dark:hover:text-emerald-100">
                                                Apply
                                            </button>
                                        </div>
                                    @endif
                                </div>
                                <x-input label="Min Bid Increment (Rs.) *" wire:model="min_bid_increment" type="number" step="0.01" icon="o-plus-circle" hint="Algorithm 2 dynamic step minimum increment" />
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Product Information --}}
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 dark:bg-[#181A1F] dark:border-gray-800 dark:shadow-none">
                    <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2 dark:text-gray-100">
                        <x-icon name="o-information-circle" class="w-6 h-6 text-primary" />
                        Basic Information & Second-Hand Condition
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-input label="Product Name" wire:model="name" placeholder="e.g. iPhone 13 Pro 128GB - Lightly Used" icon="o-tag" />
                        </div>

                        <x-select label="Category" wire:model="sub_category_id" :options="$subCategories" placeholder="Select Category" icon="o-squares-2x2" />

                        <x-select label="Condition" wire:model.live="condition" :options="$conditionOptions" icon="o-sparkles" />

                        <x-input label="Usage Duration (for second-hand)" wire:model="usage_duration" placeholder="e.g. 6 Months / 1 Year" icon="o-clock" hint="How long the product was used" />

                        <x-input label="Retail / Original Price (Rs.)" wire:model.live="retail_price" type="number" step="0.01" icon="o-banknotes" hint="MSRP / Original Buying Price" />

                        <x-input label="Purchase Date" wire:model.live="purchase_date" type="date" icon="o-calendar-days" hint="When you originally bought the item (used for estimated value)" />

                        @if($listing_type === 'direct_seller')
                            <x-input label="Selling Price (Rs.)" wire:model="sale_price" type="number" step="0.01" icon="o-currency-dollar" hint="Your asking price" />
                        @endif

                        <x-select label="Price Type" wire:model="negotiable" :options="$negotiabilityOptions" icon="o-adjustments-horizontal" />

                        <x-input label="Quantity" wire:model="quantity" type="number" icon="o-archive-box" />

                        <x-input label="City / Region Location" wire:model="location" placeholder="e.g. Kathmandu, Nepal" icon="o-map-pin" />

                        <div class="flex items-center pt-4">
                            <x-checkbox label="Delivery Available" wire:model="delivery_available" />
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <x-textarea label="Description" wire:model="description" rows="4" placeholder="Describe the product condition, reasons for selling, inclusions/accessories, flaws if any..." />
                        <x-textarea label="Specifications" wire:model="specifications" rows="3" placeholder="Brand: Apple&#10;RAM: 8GB&#10;Battery Health: 89%" />
                    </div>
                </div>

                {{-- Meetup Location for Direct Sell --}}
                @if($listing_type === 'direct_seller')
                    <div class="bg-emerald-50/60 rounded-3xl p-6 shadow-sm border border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-900/60">
                        <h2 class="text-xl font-black text-emerald-950 mb-2 flex items-center gap-2 dark:text-emerald-200">
                            <x-icon name="o-map-pin" class="w-6 h-6 text-emerald-600" />
                            Meetup Location & Handover Setup
                        </h2>
                        <p class="text-sm text-emerald-800 mb-6 dark:text-emerald-300">Specify the exact location where you can meet buyers for product inspection & sale handover.</p>

                        <div class="space-y-4">
                            <x-input label="Meetup Place / Location for Sale *" wire:model="meetup_location" placeholder="e.g. Koteshwor Chowk / New Road Complex, Kathmandu" icon="o-map-pin" hint="Location where buyer will inspect & receive the item" />

                            <x-textarea label="Meetup Availability & Instructions" wire:model="meetup_instructions" rows="2" placeholder="e.g. Available on weekdays after 5 PM, weekends anytime near New Road Mall." />
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right Side: Images & Actions --}}
            <div class="space-y-6">

                {{-- Image Upload --}}
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 dark:bg-[#181A1F] dark:border-gray-800 dark:shadow-none">
                    <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2 dark:text-gray-100">
                        <x-icon name="o-camera" class="w-6 h-6 text-primary" />
                        Product Photos
                    </h2>

                    <div class="space-y-4">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-2xl cursor-pointer hover:bg-gray-50 transition-colors dark:border-gray-700 dark:hover:bg-gray-800/60">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <x-icon name="o-cloud-arrow-up" class="w-8 h-8 text-gray-400 mb-2" />
                                <p class="text-sm text-gray-500 font-semibold dark:text-gray-400">Click to upload photos</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Clear real photos build buyer trust</p>
                            </div>
                            <input type="file" wire:model="newImages" class="hidden" multiple accept="image/*" />
                        </label>
                        @error('newImages') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                        <div class="grid grid-cols-2 gap-3 mt-4">
                            {{-- Existing Images --}}
                            @foreach($existingImages as $image)
                                <div class="relative group aspect-square rounded-xl overflow-hidden border border-gray-100 dark:border-gray-800">
                                    <img src="{{ Storage::url($image['path']) }}" class="w-full h-full object-cover" />
                                    <button type="button" wire:click="removeExistingImage({{ $image['id'] }})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                        <x-icon name="o-x-mark" class="w-4 h-4" />
                                    </button>
                                </div>
                            @endforeach

                            {{-- New Images --}}
                            @foreach($newImages as $index => $image)
                                <div class="relative group aspect-square rounded-xl overflow-hidden border border-gray-100 dark:border-gray-800">
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
                    <h3 class="font-black text-lg mb-2">Ready to list on Sajha?</h3>
                    <p class="text-white/80 text-sm mb-6">Ensure your meetup location and selling price are accurate. Once approved, your product will be published to buyers across Nepal.</p>

                    <div class="space-y-3">
                        <x-button :label="$product ? 'Update Listing' : 'Submit for Marketplace Review'" type="submit" class="btn-primary w-full bg-white text-[#1F6F5F] border-none hover:bg-gray-100" spinner="save" />
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
