<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class CategorySetup extends Component
{
    use Toast, WithFileUploads, WithPagination;

    public string $search = '';

    public bool $categoryModal = false;

    public ?Category $editingCategory = null;

    // Form fields
    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public $image;

    public ?int $parent_id = null;

    public string $icon = '';

    public string $color = '#000000';

    public string $status = 'active';

    public int $sort_order = 0;

    protected $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:categories,slug',
        'description' => 'nullable|string',
        'image' => 'nullable|image|max:1024',
        'parent_id' => 'nullable|exists:categories,id',
        'icon' => 'nullable|string',
        'color' => 'nullable|string',
        'status' => 'required|in:active,inactive',
        'sort_order' => 'required|integer',
    ];

    public function updatedName($value)
    {
        $this->slug = Str::slug($value);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->categoryModal = true;
    }

    public function editCategory(Category $category)
    {
        $this->editingCategory = $category;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description ?? '';
        $this->parent_id = $category->parent_id;
        $this->icon = $category->icon ?? '';
        $this->color = $category->color ?? '#000000';
        $this->status = $category->status;
        $this->sort_order = $category->sort_order;
        $this->categoryModal = true;
    }

    public function saveCategory()
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,'.($this->editingCategory?->id ?? 'NULL'),
            'description' => 'nullable|string',
            'image' => $this->image ? 'image|max:1024' : 'nullable',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'required|integer',
        ]);

        if ($this->image) {
            // Delete old image if exists
            if ($this->editingCategory && $this->editingCategory->image) {
                Storage::disk('public')->delete($this->editingCategory->image);
            }
            $data['image'] = $this->image->store('categories', 'public');
        }

        if ($this->editingCategory) {
            $this->editingCategory->update($data);
            $this->success('Category updated successfully.');
        } else {
            Category::create($data);
            $this->success('Category created successfully.');
        }

        $this->categoryModal = false;
        $this->resetForm();
    }

    public function removeImage()
    {
        if ($this->editingCategory && $this->editingCategory->image) {
            Storage::disk('public')->delete($this->editingCategory->image);
            $this->editingCategory->update(['image' => null]);
            $this->success('Image removed.');
        }
        $this->image = null;
    }

    public function deleteCategory(Category $category)
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        $category->delete();
        $this->success('Category deleted successfully.');
    }

    public function toggleStatus(Category $category)
    {
        $category->status = $category->status === 'active' ? 'inactive' : 'active';
        $category->save();
        $this->success('Status updated.');
    }

    private function resetForm()
    {
        $this->editingCategory = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->image = null;
        $this->parent_id = null;
        $this->icon = '';
        $this->color = '#000000';
        $this->status = 'active';
        $this->sort_order = 0;
    }

    public function render()
    {
        $categories = Category::with('parent')
            ->where('name', 'like', '%'.$this->search.'%')
            ->orderBy('sort_order')
            ->paginate(10);

        $parentCategories = Category::whereNull('parent_id')
            ->when($this->editingCategory, fn ($q) => $q->where('id', '!=', $this->editingCategory->id))
            ->get();

        return view('livewire.admin.category-setup', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
        ]);
    }
}
