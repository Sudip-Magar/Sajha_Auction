<?php

namespace App\Livewire\User;

use App\Enums\ProductAuctionType;
use App\Enums\ProductSaleType;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Notifications\NewProductUploadedNotification;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
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

    public mixed $category_id = null;

    public string $listing_type = 'direct_seller';

    public string $condition = 'new';

    public mixed $retail_price = null;

    public mixed $sale_price = null;

    public mixed $quantity = 1;

    public ?string $location = null;

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

    public function mount(?Product $product = null): void
    {
        if (! Auth::user()->is_seller) {
            $this->redirect(route('home'), navigate: true);

            return;
        }

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
        $this->category_id = $this->product->category_id;
        $this->listing_type = $this->product->listing_type->value;
        $this->condition = $this->product->condition;
        $this->retail_price = (float) $this->product->retail_price;
        $this->sale_price = (float) $this->product->sale_price;
        $this->quantity = $this->product->quantity;
        $this->location = $this->product->location;
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

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'listing_type' => 'required|in:direct_seller,auction',
            'condition' => 'required|in:new,like-new,used',
            'retail_price' => 'nullable|numeric|min:0',
            'sale_price' => 'required_if:listing_type,direct_seller|nullable|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'delivery_available' => 'boolean',
            'specifications' => 'nullable|string',
            'newImages.*' => 'image|max:2048',
        ];

        if ($this->listing_type === 'auction') {
            $rules['auction_type'] = 'required|in:traditional';
            $rules['auction_start_date_en'] = 'required|date|after_or_equal:today';
            $rules['auction_start_time'] = 'required';
            $rules['auction_start_np'] = 'required';

            $rules['starting_bid'] = 'required|numeric|min:0';
            $rules['auction_end_date_en'] = 'required|date|after:auction_start_date_en';
            $rules['auction_end_time'] = 'required';
            $rules['auction_end_np'] = 'required';
        }

        $this->validate($rules);

        if (count($this->existingImages) === 0 && count($this->newImages) === 0) {
            $this->addError('newImages', 'Please upload at least one image.');

            return;
        }

        try {
            DB::transaction(function () {
                $productData = [
                    'seller_id' => Auth::id(),
                    'category_id' => $this->category_id,
                    'name' => $this->name,
                    'description' => $this->description,
                    'specifications' => $this->specifications,
                    'condition' => $this->condition,
                    'quantity' => $this->quantity,
                    'retail_price' => $this->retail_price,
                    'sale_price' => $this->listing_type === 'direct_seller' ? $this->sale_price : null,
                    'location' => $this->location,
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
                        'sort_order' => count($this->existingImages) + $index,
                    ]);
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

                if (! $this->product) {
                    $this->notifyAdmins($product);
                }
            });

            $this->success($this->product ? 'Product updated successfully.' : 'Product uploaded for approval.');
            $this->redirect(route('user.products'), navigate: true);

        } catch (Throwable $e) {
            Log::error('ManageProduct Error: '.$e->getMessage());
            $this->error('An error occurred while saving the product.');
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
            'categories' => Category::where('status', 'active')->get(),
        ]);
    }
}
