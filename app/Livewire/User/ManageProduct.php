<?php

namespace App\Livewire\User;

use App\Enums\ProductAuctionType;
use App\Enums\ProductImageType;
use App\Enums\ProductNegotiability;
use App\Enums\ProductSaleType;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubCategory;
use App\Notifications\NewProductUploadedNotification;
use App\Services\AuctionEngineService;
use App\Services\AuctionValuationService;
use App\Services\HtmlSanitizerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Throwable;

#[Layout('layouts.app')]
class ManageProduct extends Component
{
    use Toast, WithFileUploads;

    public ?Product $product = null;

    // Product Basic Info
    public string $name = '';

    public string $description = '';

    public mixed $sub_category_id = null;

    public string $listing_type = 'direct_seller';

    public string $condition = 'like-new';

    public ?string $usage_duration = null;

    public ?string $purchase_date = null;

    public string $purchase_date_np = '';

    public mixed $retail_price = null;

    public mixed $sale_price = null;

    public string $negotiable = 'fixed';

    public mixed $quantity = 1;

    public ?string $location = null;

    public ?string $meetup_location = null;

    public ?string $meetup_instructions = null;

    public bool $delivery_available = false;

    public ?string $specifications = null;

    // Auction Info
    public string $auction_type = 'traditional';

    public string $auction_start_np = '';

    public string $auction_start_date_en = '';

    public string $auction_start_time = '';

    public string $auction_end_np = '';

    public string $auction_end_date_en = '';

    public string $auction_end_time = '';

    // Traditional Auction Details
    public mixed $starting_bid = null;

    public mixed $reserve_price = null;

    public mixed $min_bid_increment = 100;

    public int $timer_start_seconds = 60;

    public int $timer_reset_seconds = 15;

    // Images
    public array $newImages = [];

    public array $existingImages = [];

    public array $imagesToDelete = [];

    // Proof Images (e.g. warranty / guarantee card)
    public array $newProofImages = [];

    public array $existingProofImages = [];

    public array $proofImagesToDelete = [];

    public function mount(?Product $product = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->is_seller) {
            $this->warning('You must be an approved seller to create or edit products.');
            $this->redirect(route('user.products'), navigate: true);

            return;
        }

        if (! $user->is_auction_allowed) {
            $this->listing_type = 'direct_seller';
        }

        $now = Carbon::now();
        $this->auction_start_date_en = $now->copy()->addDay()->format('Y-m-d');
        $this->auction_start_time = $now->format('H:i');
        $this->auction_end_date_en = $now->copy()->addDay()->format('Y-m-d');
        $this->auction_end_time = $now->copy()->addHours(5)->format('H:i');

        if ($product && $product->exists) {
            if ((int) $product->seller_id !== (int) Auth::id()) {
                abort(403);
            }

            if ($product->is_approved) {
                $this->error('Approved products cannot be edited.');
                $this->redirect(route('user.products'), navigate: true);

                return;
            }

            $this->product = $product;
            $this->loadProductData();
        }
    }

    private function loadProductData(): void
    {
        $this->name = $this->product->name;
        $this->description = $this->product->description;
        $this->sub_category_id = $this->product->sub_category_id;
        $this->listing_type = $this->product->listing_type->value;
        $this->condition = $this->product->condition;
        $this->usage_duration = $this->product->usage_duration;
        $this->purchase_date = $this->product->purchase_date?->format('Y-m-d');
        $this->retail_price = (float) $this->product->retail_price;
        $this->sale_price = (float) $this->product->sale_price;
        $this->negotiable = $this->product->negotiable?->value ?? ProductNegotiability::FIXED->value;
        $this->quantity = $this->product->quantity;
        $this->location = $this->product->location;
        $this->meetup_location = $this->product->meetup_location;
        $this->meetup_instructions = $this->product->meetup_instructions;
        $this->delivery_available = $this->product->delivery_available;
        $this->specifications = $this->product->specifications;

        if ($this->listing_type === ProductSaleType::AUCTION->value && $this->product->auction) {
            $auction = $this->product->auction;
            $this->auction_type = $auction->auction_type;

            if ($auction->start_time) {
                $this->auction_start_date_en = $auction->start_time->format('Y-m-d');
                $this->auction_start_time = $auction->start_time->format('H:i');
                $this->auction_start_np = $auction->start_time_np ?? '';
            }

            if ($auction->end_time) {
                $this->auction_end_date_en = $auction->end_time->format('Y-m-d');
                $this->auction_end_time = $auction->end_time->format('H:i');
                $this->auction_end_np = $auction->end_time_np ?? '';
            }

            if ($this->auction_type === ProductAuctionType::TRADITIONAL->value && $auction->traditionalAuction) {
                $trad = $auction->traditionalAuction;
                $this->starting_bid = (float) $trad->starting_bid;
                $this->reserve_price = $trad->reserve_price ? (float) $trad->reserve_price : null;
                $this->min_bid_increment = (float) $trad->min_bid_increment;
                $this->timer_start_seconds = $trad->timer_start_seconds;
                $this->timer_reset_seconds = $trad->timer_reset_seconds;
            }
        }

        $this->existingImages = $this->product->images
            ->map(fn (ProductImage $image): array => [
                'id' => $image->id,
                'path' => $image->path,
            ])
            ->all();

        $this->existingProofImages = $this->product->proofImages
            ->map(fn (ProductImage $image): array => [
                'id' => $image->id,
                'path' => $image->path,
            ])
            ->all();
    }

    public function removeExistingImage(int $imageId): void
    {
        $this->imagesToDelete[] = $imageId;
        $this->imagesToDelete = array_values(array_unique($this->imagesToDelete));

        $this->existingImages = array_values(array_filter(
            $this->existingImages,
            fn (array $image): bool => $image['id'] !== $imageId
        ));
    }

    public function removeNewImage(int $index): void
    {
        unset($this->newImages[$index]);
        $this->newImages = array_values($this->newImages);
    }

    public function removeExistingProofImage(int $imageId): void
    {
        $this->proofImagesToDelete[] = $imageId;
        $this->proofImagesToDelete = array_values(array_unique($this->proofImagesToDelete));

        $this->existingProofImages = array_values(array_filter(
            $this->existingProofImages,
            fn (array $image): bool => $image['id'] !== $imageId
        ));
    }

    public function removeNewProofImage(int $index): void
    {
        unset($this->newProofImages[$index]);
        $this->newProofImages = array_values($this->newProofImages);
    }

    public function updatedListingType($value): void
    {
        if (! Auth::user()?->is_auction_allowed && $value === 'auction') {
            $this->listing_type = 'direct_seller';
            $this->warning('Your account is not approved for hosting auctions.');
        }
    }

    public function getRecommendedReserveProperty(): ?float
    {
        $sellerVal = (float) ($this->retail_price ?? 0);
        $startBid = (float) ($this->starting_bid ?? 0);

        if ($sellerVal <= 0 && $startBid <= 0) {
            return null;
        }

        return AuctionEngineService::calculateOptimalReserve($sellerVal, $startBid);
    }

    public function applyRecommendedReserve(): void
    {
        if ($this->recommendedReserve) {
            $this->reserve_price = $this->recommendedReserve;
            $this->success('Applied Myerson optimal reserve price: Rs. '.number_format($this->recommendedReserve, 2));
        }
    }

    public function getEstimatedValueProperty(): ?float
    {
        return AuctionValuationService::calculateEstimatedValue(
            $this->retail_price !== null && $this->retail_price !== '' ? (float) $this->retail_price : null,
            $this->purchase_date ? Carbon::parse($this->purchase_date) : null,
            $this->condition
        );
    }

    public function getSuggestedStartingPriceProperty(): ?float
    {
        return AuctionValuationService::calculateSuggestedStartingPrice($this->estimatedValue);
    }

    public function applySuggestedStartingPrice(): void
    {
        if ($this->suggestedStartingPrice) {
            $this->starting_bid = $this->suggestedStartingPrice;
            $this->success('Applied suggested starting price: Rs. '.number_format($this->suggestedStartingPrice, 2));
        }
    }

    public function save(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->is_seller) {
            $this->error('You must be an approved seller to upload products.');
            $this->redirect(route('user.products'), navigate: true);

            return;
        }

        if (! $user->is_auction_allowed) {
            $this->listing_type = 'direct_seller';
        }

        $allowedListingTypes = $user->is_auction_allowed ? 'direct_seller,auction' : 'direct_seller';

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'listing_type' => 'required|in:'.$allowedListingTypes,
            'condition' => 'required|in:new,like-new,lightly-used,well-used,refurbished,used',
            'usage_duration' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date|before_or_equal:today',
            'retail_price' => 'nullable|numeric|min:0',
            'sale_price' => 'required_if:listing_type,direct_seller|nullable|numeric|min:0',
            'negotiable' => 'required|in:'.ProductNegotiability::NEGOTIABLE->value.','.ProductNegotiability::FIXED->value,
            'quantity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'meetup_location' => 'required_if:listing_type,direct_seller|nullable|string|max:255',
            'meetup_instructions' => 'nullable|string',
            'delivery_available' => 'boolean',
            'specifications' => 'nullable|string',
            'newImages.*' => 'image|max:2048',
            'newProofImages.*' => 'nullable|image|max:2048',
        ];

        if ($this->listing_type === 'auction') {
            $rules['auction_type'] = 'required|in:traditional';
            $rules['auction_start_date_en'] = $this->product?->exists
                ? 'required|date'
                : 'required|date|after:today';
            $rules['auction_start_time'] = 'required';
            $rules['auction_start_np'] = 'required';

            $rules['starting_bid'] = 'required|numeric|min:0';
            $rules['auction_end_date_en'] = 'required|date|after_or_equal:auction_start_date_en';
            $rules['auction_end_time'] = 'required';
            $rules['auction_end_np'] = 'required';
        }

        $this->validate($rules);

        if ($this->listing_type === 'auction') {
            $startTime = Carbon::parse($this->auction_start_date_en.' '.$this->auction_start_time);
            $endTime = Carbon::parse($this->auction_end_date_en.' '.$this->auction_end_time);

            if ($endTime->lessThanOrEqualTo($startTime)) {
                $this->addError('auction_end_time', 'The auction end time must be after the start time.');

                return;
            }
        }

        if (count($this->existingImages) === 0 && count($this->newImages) === 0) {
            $this->addError('newImages', 'Please upload at least one image.');

            return;
        }

        try {
            DB::transaction(function () {
                $isNewProduct = ! $this->product;
                $productData = [
                    'seller_id' => Auth::id(),
                    'sub_category_id' => $this->sub_category_id,
                    'name' => $this->name,
                    'description' => HtmlSanitizerService::sanitize($this->description),
                    'specifications' => $this->specifications !== null && $this->specifications !== ''
                        ? HtmlSanitizerService::sanitize($this->specifications)
                        : null,
                    'condition' => $this->condition,
                    'usage_duration' => $this->usage_duration,
                    'purchase_date' => $this->purchase_date,
                    'quantity' => $this->quantity,
                    'retail_price' => $this->retail_price,
                    'sale_price' => $this->listing_type === 'direct_seller' ? $this->sale_price : null,
                    'negotiable' => $this->negotiable,
                    'location' => $this->location,
                    'meetup_location' => $this->meetup_location,
                    'meetup_instructions' => $this->meetup_instructions,
                    'delivery_available' => $this->delivery_available,
                    'listing_type' => $this->listing_type,
                    'status' => 'pending',
                ];

                if ($this->product) {
                    $this->product->update($productData);
                    $product = $this->product;
                } else {
                    $product = Product::create($productData);
                }

                // Handle Images
                foreach ($this->imagesToDelete as $imageId) {
                    $image = ProductImage::find($imageId);
                    if ($image) {
                        Storage::disk('public')->delete($image->path);
                        $image->delete();
                    }
                }

                foreach ($this->newImages as $index => $imageFile) {
                    $path = $imageFile->store('products', 'public');
                    $product->images()->create([
                        'path' => $path,
                        'image_type' => ProductImageType::GENERAL,
                        'sort_order' => count($this->existingImages) + $index,
                    ]);
                }

                // Handle Proof Images
                foreach ($this->proofImagesToDelete as $imageId) {
                    $image = ProductImage::find($imageId);
                    if ($image) {
                        Storage::disk('public')->delete($image->path);
                        $image->delete();
                    }
                }

                foreach ($this->newProofImages as $index => $imageFile) {
                    $path = $imageFile->store('products/proofs', 'public');
                    $product->proofImages()->create([
                        'path' => $path,
                        'image_type' => ProductImageType::PROOF,
                        'sort_order' => count($this->existingProofImages) + $index,
                    ]);
                }

                // Log product timeline
                if ($isNewProduct) {
                    $product->logTimeline(
                        'uploaded',
                        'Product Uploaded for Review',
                        'Product listed on marketplace by seller ('.Auth::user()->name.'). Awaiting admin approval.',
                        Auth::user()
                    );
                } else {
                    $product->logTimeline(
                        'updated',
                        'Product Details Updated',
                        'Product listing information updated by seller.',
                        Auth::user()
                    );
                }

                // Handle Auction
                if ($this->listing_type === 'auction') {
                    $startTime = Carbon::parse($this->auction_start_date_en.' '.$this->auction_start_time);
                    $endTime = Carbon::parse($this->auction_end_date_en.' '.$this->auction_end_time);

                    $auction = $product->auction()->updateOrCreate([], [
                        'auction_type' => $this->auction_type,
                        'start_time' => $startTime,
                        'start_time_np' => $this->auction_start_np,
                        'end_time' => $endTime,
                        'end_time_np' => $this->auction_end_np,
                        'status' => 'pending',
                        'current_price' => $this->starting_bid,
                    ]);

                    $auction->traditionalAuction()->updateOrCreate([], [
                        'starting_bid' => $this->starting_bid,
                        'reserve_price' => $this->reserve_price,
                        'min_bid_increment' => $this->min_bid_increment,
                        'timer_start_seconds' => $this->timer_start_seconds,
                        'timer_reset_seconds' => $this->timer_reset_seconds,
                    ]);
                } else {
                    if ($product->auction) {
                        $product->auction->delete();
                    }
                }

                if ($isNewProduct) {
                    $this->notifyAdmins($product);
                }
            });

            $this->success($this->product ? 'Product updated successfully.' : 'Product uploaded for approval.');
            $this->redirect(route('user.products'), navigate: true);

        } catch (Throwable $e) {
            Log::error('ManageProduct Error: '.$e->getMessage());
            $this->error('An error occurred while saving the product.');
            dd($e->getMessage());
        }
    }

    private function notifyAdmins(Product $product): void
    {
        $admins = Admin::all();
        foreach ($admins as $admin) {
            $admin->notify(new NewProductUploadedNotification($product));
        }
    }

    public function render(): View
    {
        return view('livewire.user.manage-product', [
            'isAuctionAllowed' => (bool) Auth::user()?->is_auction_allowed,
            'subCategories' => SubCategory::query()
                ->with('category')
                ->where('status', 'active')
                ->whereHas('category', function ($query): void {
                    $query->where('status', 'active');
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (SubCategory $subCategory): array => [
                    'id' => $subCategory->id,
                    'name' => $subCategory->category?->name
                        ? $subCategory->category->name.' - '.$subCategory->name
                        : $subCategory->name,
                ])
                ->all(),
            'negotiabilityOptions' => [
                ['id' => ProductNegotiability::FIXED->value, 'name' => ProductNegotiability::FIXED->label()],
                ['id' => ProductNegotiability::NEGOTIABLE->value, 'name' => ProductNegotiability::NEGOTIABLE->label()],
            ],
            'conditionOptions' => [
                ['id' => 'new', 'name' => 'Brand New'],
                ['id' => 'like-new', 'name' => 'Like New (Minimal Use)'],
                ['id' => 'lightly-used', 'name' => 'Lightly Used'],
                ['id' => 'well-used', 'name' => 'Well Used / Fair'],
                ['id' => 'refurbished', 'name' => 'Refurbished'],
            ],
        ]);
    }
}
