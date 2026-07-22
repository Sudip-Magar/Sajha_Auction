<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class SubCategorySetup extends Component
{
    use Toast, WithFileUploads, WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public bool $subCategoryModal = false;

    public ?SubCategory $editingSubCategory = null;

    public ?int $category_id = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public $image;

    public string $icon = '';

    public string $color = '#000000';

    public string $status = 'active';

    public int $sort_order = 0;

    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->subCategoryModal = true;
    }

    public function editSubCategory(SubCategory $subCategory): void
    {
        $this->editingSubCategory = $subCategory;
        $this->category_id = $subCategory->category_id;
        $this->name = $subCategory->name;
        $this->slug = $subCategory->slug;
        $this->description = $subCategory->description ?? '';
        $this->icon = $subCategory->icon ?? '';
        $this->color = $subCategory->color ?? '#000000';
        $this->status = $subCategory->status;
        $this->sort_order = $subCategory->sort_order;
        $this->subCategoryModal = true;
    }

    public function saveSubCategory(): void
    {
        $data = $this->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sub_categories', 'slug')->ignore($this->editingSubCategory?->id),
            ],
            'description' => ['nullable', 'string'],
            'image' => [$this->image ? 'image' : 'nullable', 'max:1024'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['required', 'integer'],
        ]);

        if ($this->image) {
            if ($this->editingSubCategory && $this->editingSubCategory->image) {
                Storage::disk('public')->delete($this->editingSubCategory->image);
            }

            $data['image'] = $this->image->store('sub-categories', 'public');
        }

        if ($this->editingSubCategory) {
            $this->editingSubCategory->update($data);
            $this->success('Sub-category updated successfully.');
        } else {
            SubCategory::create($data);
            $this->success('Sub-category created successfully.');
        }

        $this->subCategoryModal = false;
        $this->resetForm();
    }

    public function removeImage(): void
    {
        if ($this->editingSubCategory && $this->editingSubCategory->image) {
            Storage::disk('public')->delete($this->editingSubCategory->image);
            $this->editingSubCategory->update(['image' => null]);
            $this->success('Image removed.');
        }

        $this->image = null;
    }

    public function deleteSubCategory(SubCategory $subCategory): void
    {
        if ($subCategory->products()->exists()) {
            $this->error('This sub-category is used by products and cannot be deleted.');

            return;
        }

        if ($subCategory->image) {
            Storage::disk('public')->delete($subCategory->image);
        }

        $subCategory->delete();
        $this->success('Sub-category deleted successfully.');
    }

    public function toggleStatus(SubCategory $subCategory): void
    {
        $subCategory->status = $subCategory->status === 'active' ? 'inactive' : 'active';
        $subCategory->save();

        $this->success('Status updated.');
    }

    private function resetForm(): void
    {
        $this->editingSubCategory = null;
        $this->category_id = Category::query()->orderBy('sort_order')->value('id');
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->image = null;
        $this->icon = '';
        $this->color = '#000000';
        $this->status = 'active';
        $this->sort_order = 0;
    }

    public function render(): View
    {
        $subCategories = SubCategory::query()
            ->with('category')
            ->withCount('products')
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%')
                        ->orWhereHas('category', function (Builder $query): void {
                            $query->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->categoryFilter !== '', function (Builder $query): void {
                $query->where('category_id', (int) $this->categoryFilter);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.sub-category-setup', [
            'subCategories' => $subCategories,
            'categoryOptions' => Category::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'totalSubCategories' => SubCategory::count(),
            'activeSubCategories' => SubCategory::where('status', 'active')->count(),
            'linkedProducts' => SubCategory::query()->has('products')->count(),
        ]);
    }
}
