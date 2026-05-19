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

    public $price;

    public $starting_bid;

    public $auction_end;

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
        if ((int) $product->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $product->loadMissing('images');

        $this->resetValidation();
        $this->editingProduct = $product;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->category_id = $product->category_id;
        $this->type = $product->type;
        $this->price = $product->price;
        $this->starting_bid = $product->starting_bid;
        $this->auction_end = $product->auction_end?->format('Y-m-d\TH:i');
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
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:sell,auction',
            'newImages' => 'array',
            'newImages.*' => 'image|max:2048',
        ];

        if ($this->type === 'sell') {
            $rules['price'] = 'required|numeric|min:0';
            $rules['starting_bid'] = 'nullable';
            $rules['auction_end'] = 'nullable';
        } else {
            $rules['starting_bid'] = 'required|numeric|min:0';
            $rules['auction_end'] = 'required|after:now';
            $rules['price'] = 'nullable';
        }

        $data = $this->validate($rules);

        if (count($this->existingImages) === 0 && count($this->newImages) === 0) {
            $this->addError('newImages', 'Please upload at least one product image.');

            return;
        }

        $data['user_id'] = Auth::id();
        $data['price'] = $this->type === 'sell' ? $this->price : null;
        $data['starting_bid'] = $this->type === 'auction' ? $this->starting_bid : null;
        $data['auction_end'] = $this->type === 'auction' ? $this->auction_end : null;

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
        $this->price = null;
        $this->starting_bid = null;
        $this->auction_end = null;
        $this->newImages = [];
        $this->existingImages = [];
        $this->imagesToDelete = [];
    }

    public function render(): View
    {
        $userProducts = Product::with(['category', 'images'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        $categories = Category::where('status', 'active')->get();

        return view('livewire.user.products', [
            'products' => $userProducts,
            'categories' => $categories,
        ]);
    }
}
