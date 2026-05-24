<?php

namespace App\Livewire\User;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Notifications\NewProductUploadedNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Products extends Component
{
    use Toast, WithFileUploads, WithPagination;

    public bool $productModal = false;

    public ?Product $editingProduct = null;

    public string $name = '';

    public string $description = '';

    public ?int $category_id = null;

    public string $type = 'sell';

    public string $condition = 'new';

    public ?float $retail_price = null;

    public ?float $sale_price = null;

    public int $stock_quantity = 1;

    public ?string $specifications = null;

    public ?string $auction_type = 'traditional';

    public ?float $starting_bid = null;

    public int $starting_price_cents = 0;

    public int $bid_increment_cents = 1;

    public int $timer_seconds = 60;

    public int $timer_extension_seconds = 15;

    public ?string $auction_end_en = null;

    public string $auction_end_np = '';

    public string $auction_end_date_en = '';

    public string $auction_end_time = '';

    public ?string $scheduled_for = null;

    public string $scheduled_for_np = '';

    public string $scheduled_for_date_en = '';

    public string $scheduled_for_time = '';

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
        $this->type = $product->type;
        $this->condition = $product->condition;
        $this->retail_price = (float) $product->retail_price;
        $this->sale_price = $product->sale_price ? (float) $product->sale_price : null;
        $this->stock_quantity = (int) $product->stock_quantity;
        $this->specifications = $product->specifications;
        $this->auction_type = $product->auction_type ?: 'traditional';
        $this->starting_bid = $product->starting_bid ? (float) $product->starting_bid : null;
        $this->starting_price_cents = (int) $product->starting_price_cents;
        $this->bid_increment_cents = (int) $product->bid_increment_cents;
        $this->timer_seconds = (int) $product->timer_seconds;
        $this->timer_extension_seconds = (int) $product->timer_extension_seconds;

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

        if ($product->scheduled_for) {
            $this->scheduled_for = $product->scheduled_for->format('Y-m-d H:i');
            $this->scheduled_for_date_en = $product->scheduled_for->format('Y-m-d');
            $this->scheduled_for_time = $product->scheduled_for->format('H:i');
            $this->scheduled_for_np = $product->scheduled_for_np ?? '';
        } else {
            $this->scheduled_for = null;
            $this->scheduled_for_date_en = '';
            $this->scheduled_for_time = '';
            $this->scheduled_for_np = '';
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
        $this->auction_end_en = $this->type === 'auction' && $this->auction_type === 'traditional' && $this->auction_end_date_en && $this->auction_end_time
            ? "{$this->auction_end_date_en} {$this->auction_end_time}"
            : null;

        $this->scheduled_for = $this->type === 'auction' && $this->scheduled_for_date_en && $this->scheduled_for_time
            ? "{$this->scheduled_for_date_en} {$this->scheduled_for_time}"
            : null;

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:sell,auction',
            'condition' => 'required|in:new,like-new,used',
            'retail_price' => 'required|numeric|min:0',
            'specifications' => 'nullable|string',
            'newImages' => 'array',
            'newImages.*' => 'image|max:2048',
        ];

        if ($this->type === 'sell') {
            $rules['sale_price'] = 'required|numeric|min:0';
            $rules['stock_quantity'] = 'required|integer|min:1';
        } else {
            $rules['auction_type'] = 'required|in:traditional,penny';

            if ($this->auction_type === 'traditional') {
                $rules['starting_bid'] = 'required|numeric|min:0';
                $rules['auction_end_en'] = 'required|after:now';
            } else {
                $rules['starting_price_cents'] = 'required|integer|min:0';
                $rules['bid_increment_cents'] = 'required|integer|min:1';
                $rules['timer_seconds'] = 'required|integer|min:10';
                $rules['timer_extension_seconds'] = 'required|integer|min:1';
            }

            if ($this->scheduled_for) {
                $rules['scheduled_for'] = 'required|after:now';
            }
        }

        $data = $this->validate($rules, [
            'auction_end_en.required' => 'The auction end date and time are required.',
            'auction_end_en.after' => 'The auction end date and time must be in the future.',
            'scheduled_for.after' => 'The scheduled auction start must be in the future.',
        ]);

        if (count($this->existingImages) === 0 && count($this->newImages) === 0) {
            $this->addError('newImages', 'Please upload at least one product image.');

            return;
        }

        $data['seller_id'] = Auth::id();
        $data['condition'] = $this->condition;
        $data['retail_price'] = $this->retail_price;
        $data['specifications'] = $this->specifications;

        if ($this->type === 'sell') {
            $data['sale_price'] = $this->sale_price;
            $data['stock_quantity'] = $this->stock_quantity;
            $data['auction_type'] = null;
            $data['starting_bid'] = null;
            $data['starting_price_cents'] = 0;
            $data['bid_increment_cents'] = 1;
            $data['timer_seconds'] = 60;
            $data['timer_extension_seconds'] = 15;
            $data['auction_start_en'] = null;
            $data['auction_start_np'] = null;
            $data['auction_end_en'] = null;
            $data['auction_end_np'] = null;
            $data['scheduled_for'] = null;
        } else {
            $data['sale_price'] = null;
            $data['stock_quantity'] = 0;
            $data['auction_type'] = $this->auction_type;
            $data['scheduled_for'] = $this->scheduled_for;

            if ($this->auction_type === 'traditional') {
                $data['starting_bid'] = $this->starting_bid;
                $data['starting_price_cents'] = 0;
                $data['bid_increment_cents'] = 1;
                $data['timer_seconds'] = 60;
                $data['timer_extension_seconds'] = 15;
                $data['auction_end_en'] = $this->auction_end_en;
                $data['auction_end_np'] = $this->auction_end_np ?: null;
            } else {
                $data['starting_bid'] = null;
                $data['starting_price_cents'] = $this->starting_price_cents;
                $data['bid_increment_cents'] = $this->bid_increment_cents;
                $data['timer_seconds'] = $this->timer_seconds;
                $data['timer_extension_seconds'] = $this->timer_extension_seconds;
                $data['auction_end_en'] = null;
                $data['auction_end_np'] = null;
            }
        }

        unset($data['newImages']);

        if ($this->editingProduct) {
            $this->editingProduct->update($data);
            $this->syncProductImages($this->editingProduct);

            $this->success('Product updated successfully.');
        } else {
            $product = Product::create($data);
            $this->storeNewImages($product);

            $admins = Admin::all();
            foreach ($admins as $admin) {
                try {
                    $admin->notify(new NewProductUploadedNotification($product));
                } catch (BroadcastException $exception) {
                    Log::warning('Product upload notification broadcast failed.', [
                        'product_id' => $product->id,
                        'admin_id' => $admin->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }

            $this->success('Product uploaded and waiting for admin approval.');
        }

        $this->productModal = false;
        $this->resetForm();
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
        $this->type = 'sell';
        $this->condition = 'new';
        $this->retail_price = null;
        $this->sale_price = null;
        $this->stock_quantity = 1;
        $this->specifications = null;
        $this->auction_type = 'traditional';
        $this->starting_bid = null;
        $this->starting_price_cents = 0;
        $this->bid_increment_cents = 1;
        $this->timer_seconds = 60;
        $this->timer_extension_seconds = 15;
        $this->auction_end_en = null;
        $this->auction_end_np = '';
        $this->auction_end_date_en = '';
        $this->auction_end_time = '';
        $this->scheduled_for = null;
        $this->scheduled_for_np = '';
        $this->scheduled_for_date_en = '';
        $this->scheduled_for_time = '';
        $this->newImages = [];
        $this->existingImages = [];
        $this->imagesToDelete = [];
    }

    public function render(): View
    {
        $userProducts = Product::with(['category', 'images'])
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
