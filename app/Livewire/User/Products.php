<?php

namespace App\Livewire\User;

use App\Enums\ProductAuctionType;
use App\Enums\ProductSaleType;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Notifications\NewProductUploadedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Throwable;

#[Layout('layouts.app')]
class Products extends Component
{
    use Toast, WithFileUploads, WithPagination;

    public function getListeners(): array
    {
        $userId = Auth::id();

        if (! $userId) {
            return [
                'userNotificationReceived' => '$refresh',
            ];
        }

        return [
            'userNotificationReceived' => '$refresh',
            "echo-notification:App.Models.User.{$userId}" => '$refresh',
        ];
    }

    public bool $productModal = false;

    public ?Product $editingProduct = null;

    public string $name = '';

    public string $description = '';

    public mixed $category_id = null;

    public string $type = 'direct_seller';

    public string $condition = 'new';

    public mixed $retail_price = null;

    public mixed $sale_price = null;

    public mixed $stock_quantity = 1;

    public ?string $specifications = null;

    public ?string $auction_type = 'traditional';

    public mixed $starting_bid = null;

    public mixed $starting_price_cents = 0;

    public mixed $bid_increment_cents = 1;

    public mixed $timer_seconds = 60;

    public mixed $timer_extension_seconds = 15;

    public ?string $auction_start_en = null;

    public string $auction_start_np = '';

    public string $auction_start_date_en = '';

    public string $auction_start_time = '';

    public ?string $auction_end_en = null;

    public string $auction_end_np = '';

    public string $auction_end_date_en = '';

    public string $auction_end_time = '';

    public array $newImages = [];

    /** @var array<int, array{id:int, path:string}> */
    public array $existingImages = [];

    /** @var array<int> */
    public array $imagesToDelete = [];

    public function mount(): mixed
    {
        if (! Auth::user()->is_seller) {
            return $this->redirect(route('home'), navigate: true);
        }

        return null;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->productModal = true;
        $this->dispatch('init-nepali-date-pickers');
    }

    public function editProduct(Product $product): void
    {
        if ((int) $product->seller_id !== (int) Auth::id()) {
            abort(403);
        }

        $product->loadMissing('images');

        $this->resetValidation();
        $this->editingProduct = $product;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->category_id = $product->category_id;
        $this->type = $product->type->value;
        $this->condition = $product->condition;
        $this->retail_price = (float) $product->retail_price;
        $this->sale_price = $product->sale_price ? (float) $product->sale_price : null;
        $this->stock_quantity = (int) $product->stock_quantity;
        $this->specifications = $product->specifications;
        $this->auction_type = $product->auction_type?->value ?: ProductAuctionType::TRADITIONAL->value;
        $this->starting_bid = $product->starting_bid ? (float) $product->starting_bid : null;
        $this->starting_price_cents = (int) $product->starting_price_cents;
        $this->bid_increment_cents = (int) $product->bid_increment_cents;
        $this->timer_seconds = (int) $product->timer_seconds;
        $this->timer_extension_seconds = (int) $product->timer_extension_seconds;

        if ($product->auction_start_en) {
            $this->auction_start_en = $product->auction_start_en->format('Y-m-d H:i');
            $this->auction_start_date_en = $product->auction_start_en->format('Y-m-d');
            $this->auction_start_time = $product->auction_start_en->format('H:i');
            $this->auction_start_np = $product->auction_start_np ?? '';
        } else {
            $this->auction_start_en = null;
            $this->auction_start_date_en = '';
            $this->auction_start_time = '';
            $this->auction_start_np = '';
        }

        if ($product->auction_end_en) {
            $this->auction_end_en = $product->auction_end_en->format('Y-m-d H:i');
            $this->auction_end_date_en = $product->auction_end_en->format('Y-m-d');
            $this->auction_end_time = $product->auction_end_en->format('H:i');
            $this->auction_end_np = $product->auction_end_np ?? '';
        } else {
            $this->auction_end_en = null;
            $this->auction_end_date_en = '';
            $this->auction_end_time = '';
            $this->auction_end_np = '';
        }

        $this->newImages = [];
        $this->imagesToDelete = [];
        $this->existingImages = $product->images
            ->map(fn (ProductImage $image): array => [
                'id' => $image->id,
                'path' => $image->path,
            ])
            ->all();
        $this->productModal = true;
        $this->dispatch('init-nepali-date-pickers');
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
        if (! array_key_exists($index, $this->newImages)) {
            return;
        }

        unset($this->newImages[$index]);
        $this->newImages = array_values($this->newImages);
    }

    public function saveProduct(): void
    {
        $this->auction_start_en = $this->type === ProductSaleType::AUCTION->value && $this->auction_start_date_en && $this->auction_start_time
            ? "{$this->auction_start_date_en} {$this->auction_start_time}"
            : null;

        $this->auction_end_en = $this->type === ProductSaleType::AUCTION->value && $this->auction_type === ProductAuctionType::TRADITIONAL->value && $this->auction_end_date_en && $this->auction_end_time
            ? "{$this->auction_end_date_en} {$this->auction_end_time}"
            : null;

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:'.ProductSaleType::DIRECT_SELLER->value.','.ProductSaleType::AUCTION->value,
            'condition' => 'required|in:new,like-new,used',
            'retail_price' => 'required|numeric|min:0',
            'specifications' => 'nullable|string',
            'newImages' => 'array',
            'newImages.*' => 'image|max:2048',
        ];

        if ($this->type === ProductSaleType::DIRECT_SELLER->value) {
            $rules['sale_price'] = 'required|numeric|min:0';
            $rules['stock_quantity'] = 'required|integer|min:1';
        } else {
            $rules['auction_type'] = 'required|in:'.ProductAuctionType::TRADITIONAL->value.','.ProductAuctionType::PENNY->value;
            $rules['stock_quantity'] = 'required|integer|min:1';
            $rules['auction_start_en'] = 'required|after_or_equal:'.now()->addDay()->format('Y-m-d H:i');
            $rules['auction_start_np'] = 'required|string|max:20';

            if ($this->auction_type === ProductAuctionType::TRADITIONAL->value) {
                $rules['starting_bid'] = 'required|numeric|min:0';
                $rules['auction_end_en'] = 'required|after:auction_start_en';
                $rules['auction_end_np'] = 'required|string|max:20';
            } else {
                $rules['starting_price_cents'] = 'required|integer|min:0';
                $rules['bid_increment_cents'] = 'required|integer|min:1';
                $rules['timer_seconds'] = 'required|integer|min:10';
                $rules['timer_extension_seconds'] = 'required|integer|min:1';
            }
        }

        $data = $this->validate($rules, [
            'auction_start_en.required' => 'The auction start date and time are required.',
            'auction_start_en.after_or_equal' => 'The auction start date and time must be at least 24 hours from now.',
            'auction_start_np.required' => 'The auction start Nepali date is required.',
            'auction_end_en.required' => 'The auction end date and time are required.',
            'auction_end_en.after' => 'The auction end date and time must be after the start date and time.',
            'auction_end_np.required' => 'The auction end Nepali date is required.',
        ]);

        if (count($this->existingImages) === 0 && count($this->newImages) === 0) {
            $this->addError('newImages', 'Please upload at least one product image.');

            return;
        }

        $data['seller_id'] = Auth::id();
        $data['category_id'] = (int) $this->category_id;
        $data['condition'] = $this->condition;
        $data['retail_price'] = (float) $this->retail_price;
        $data['specifications'] = $this->specifications;

        $directSellerData = null;
        $pennyAuctionData = null;
        $traditionalAuctionData = null;

        if ($this->type === ProductSaleType::DIRECT_SELLER->value) {
            $directSellerData = [
                'price' => (float) $this->sale_price,
                'quantity' => (int) $this->stock_quantity,
            ];
        } else {
            if ($this->auction_type === ProductAuctionType::TRADITIONAL->value) {
                $traditionalAuctionData = [
                    'quantity' => (int) $this->stock_quantity,
                    'starting_bid' => (float) $this->starting_bid,
                    'bid_increment' => 1,
                    'auction_start_en' => $this->auction_start_en,
                    'auction_start_np' => $this->auction_start_np,
                    'auction_end_en' => $this->auction_end_en,
                    'auction_end_np' => $this->auction_end_np,
                ];
            } else {
                $pennyAuctionData = [
                    'quantity' => (int) $this->stock_quantity,
                    'starting_price_cents' => (int) $this->starting_price_cents,
                    'bid_increment_cents' => (int) $this->bid_increment_cents,
                    'timer_seconds' => (int) $this->timer_seconds,
                    'timer_extension_seconds' => (int) $this->timer_extension_seconds,
                    'auction_start_en' => $this->auction_start_en,
                    'auction_start_np' => $this->auction_start_np,
                ];
            }
        }

        $productData = collect($data)
            ->only([
                'name',
                'description',
                'category_id',
                'type',
                'condition',
                'retail_price',
                'specifications',
                'seller_id',
            ])
            ->all();

        try {
            DB::transaction(function () use ($productData, $directSellerData, $pennyAuctionData, $traditionalAuctionData): void {
                if ($this->editingProduct) {
                    $this->editingProduct->update($productData);
                    $this->syncListingDetails($this->editingProduct, $directSellerData, $pennyAuctionData, $traditionalAuctionData);
                    $this->syncProductImages($this->editingProduct);

                    return;
                }

                $product = Product::create($productData);
                $this->syncListingDetails($product, $directSellerData, $pennyAuctionData, $traditionalAuctionData);
                $this->storeNewImages($product);
                $this->notifyAdminsAboutProduct($product);
            });
        } catch (Throwable $exception) {
            Log::error('Product upload failed.', [
                'seller_id' => Auth::id(),
                'message' => $exception->getMessage(),
            ]);

            $this->addError('form', config('app.debug')
                ? $exception->getMessage()
                : 'Product upload failed. Please try again.');
            $this->error('Product upload failed. Please check the form and try again.');

            return;
        }

        if ($this->editingProduct) {
            $this->success('Product updated successfully.');
        } else {
            $this->success('Product uploaded and waiting for admin approval.');
        }

        $this->productModal = false;
        $this->resetForm();
    }

    private function notifyAdminsAboutProduct(Product $product): void
    {
        $product->loadMissing('user');

        foreach (Admin::all() as $admin) {
            try {
                $admin->notify(new NewProductUploadedNotification($product));
            } catch (Throwable $exception) {
                Log::warning('Product upload notification failed.', [
                    'product_id' => $product->id,
                    'admin_id' => $admin->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function deleteProduct(Product $product): void
    {
        if ((int) $product->seller_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($product->is_approved) {
            $this->error('Approved products cannot be deleted. Please contact administration.');

            return;
        }

        // Clean up images from storage
        $product->loadMissing('images');
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $product->delete();
        $this->success('Product deleted successfully.');
    }

    private function syncProductImages(Product $product): void
    {
        if ($this->imagesToDelete !== []) {
            $images = $product->images()->whereIn('id', $this->imagesToDelete)->get();

            foreach ($images as $image) {
                Storage::disk('public')->delete($image->path);
            }

            $product->images()->whereIn('id', $this->imagesToDelete)->delete();
        }

        $nextSortOrder = (int) $product->images()->max('sort_order') + 1;
        $this->storeNewImages($product, $nextSortOrder);
    }

    private function syncListingDetails(Product $product, ?array $directSellerData, ?array $pennyAuctionData, ?array $traditionalAuctionData): void
    {
        if ($directSellerData !== null) {
            $product->directSellerProduct()->updateOrCreate([], $directSellerData);
            $product->pennyAuction()->delete();
            $product->traditionalAuction()->delete();

            return;
        }

        $product->directSellerProduct()->delete();

        if ($pennyAuctionData !== null) {
            $product->pennyAuction()->updateOrCreate([], $pennyAuctionData);
            $product->traditionalAuction()->delete();

            return;
        }

        if ($traditionalAuctionData !== null) {
            $product->traditionalAuction()->updateOrCreate([], $traditionalAuctionData);
            $product->pennyAuction()->delete();
        }
    }

    private function storeNewImages(Product $product, int $startingSortOrder = 0): void
    {
        foreach ($this->newImages as $index => $image) {
            $product->images()->create([
                'path' => $image->store('products', 'public'),
                'sort_order' => $startingSortOrder + $index,
            ]);
        }
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->editingProduct = null;
        $this->name = '';
        $this->description = '';
        $this->category_id = null;
        $this->type = ProductSaleType::DIRECT_SELLER->value;
        $this->condition = 'new';
        $this->retail_price = null;
        $this->sale_price = null;
        $this->stock_quantity = 1;
        $this->specifications = null;
        $this->auction_type = ProductAuctionType::TRADITIONAL->value;
        $this->starting_bid = null;
        $this->starting_price_cents = 0;
        $this->bid_increment_cents = 1;
        $this->timer_seconds = 60;
        $this->timer_extension_seconds = 15;
        $this->auction_start_en = null;
        $this->auction_start_np = '';
        $this->auction_start_date_en = '';
        $this->auction_start_time = '';
        $this->auction_end_en = null;
        $this->auction_end_np = '';
        $this->auction_end_date_en = '';
        $this->auction_end_time = '';
        $this->newImages = [];
        $this->existingImages = [];
        $this->imagesToDelete = [];
    }

    public function render(): View
    {
        if (! Auth::user()?->is_seller) {
            $this->redirect(route('home'), navigate: true);
        }

        $userProducts = Product::with(['category', 'images', 'directSellerProduct', 'pennyAuction', 'traditionalAuction'])
            ->where('seller_id', Auth::id())
            ->latest()
            ->paginate(10);

        $categories = Category::where('status', 'active')->get();

        return view('livewire.user.products', [
            'products' => $userProducts,
            'categories' => $categories,
        ]);
    }
}
