<?php

namespace App\Livewire\User;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Notifications\NewProductUploadedNotification;
use Illuminate\Support\Facades\Auth;
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

    // Form fields
    public string $name = '';

    public string $description = '';

    public ?int $category_id = null;

    public string $type = 'sell'; // 'sell' or 'auction'

    public $price;

    public $starting_bid;

    public $auction_end;

    public $image;

    public function mount()
    {
        if (! Auth::user()->is_seller) {
            return $this->redirect(route('home'), navigate: true);
        }
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->productModal = true;
    }

    public function saveProduct()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:sell,auction',
            'image' => 'nullable|image|max:2048',
        ];

        if ($this->type === 'sell') {
            $rules['price'] = 'required|numeric|min:0';
        } else {
            $rules['starting_bid'] = 'required|numeric|min:0';
            $rules['auction_end'] = 'required|after:now';
        }

        $data = $this->validate($rules);
        $data['user_id'] = Auth::id();

        if ($this->image) {
            $data['image'] = $this->image->store('products', 'public');
        }

        $product = Product::create($data);

        // Notify Admins
        $admins = Admin::all();
        foreach ($admins as $admin) {
            $admin->notify(new NewProductUploadedNotification($product));
        }

        $this->success('Product uploaded and waiting for admin approval.');
        $this->productModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->category_id = null;
        $this->type = 'sell';
        $this->price = null;
        $this->starting_bid = null;
        $this->auction_end = null;
        $this->image = null;
    }

    public function render()
    {
        $userProducts = Product::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        $categories = Category::where('status', 'active')->get();

        return view('livewire.user.products', [
            'products' => $userProducts,
            'categories' => $categories,
        ]);
    }
}
