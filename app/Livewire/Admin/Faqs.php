<?php

namespace App\Livewire\Admin;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class Faqs extends Component
{
    use Toast, WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public bool $faqModal = false;

    public ?Faq $editingFaq = null;

    public ?int $faq_category_id = null;

    public string $question = '';

    public string $answer = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    public bool $categoryQuickAddModal = false;

    public string $newCategoryName = '';

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
        $this->faqModal = true;
    }

    public function editFaq(Faq $faq): void
    {
        $this->editingFaq = $faq;
        $this->faq_category_id = $faq->faq_category_id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->sort_order = $faq->sort_order;
        $this->is_active = $faq->is_active;
        $this->faqModal = true;
    }

    public function saveFaq(): void
    {
        $data = $this->validate([
            'faq_category_id' => ['required', 'integer', 'exists:faq_categories,id'],
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string'],
            'sort_order' => ['required', 'integer'],
            'is_active' => ['boolean'],
        ]);

        if ($this->editingFaq) {
            $this->editingFaq->update($data);
            $this->success('FAQ updated successfully.');
        } else {
            Faq::create($data);
            $this->success('FAQ created successfully.');
        }

        $this->faqModal = false;
        $this->resetForm();
    }

    public function deleteFaq(Faq $faq): void
    {
        $faq->delete();
        $this->success('FAQ deleted successfully.');
    }

    public function toggleStatus(Faq $faq): void
    {
        $faq->is_active = ! $faq->is_active;
        $faq->save();

        $this->success('Status updated.');
    }

    public function openCategoryQuickAdd(): void
    {
        $this->newCategoryName = '';
        $this->categoryQuickAddModal = true;
    }

    public function saveCategoryQuickAdd(): void
    {
        $this->validate([
            'newCategoryName' => ['required', 'string', 'max:255', Rule::unique('faq_categories', 'name')],
        ]);

        $category = FaqCategory::create([
            'name' => $this->newCategoryName,
            'slug' => Str::slug($this->newCategoryName).'-'.Str::random(4),
            'sort_order' => (int) FaqCategory::max('sort_order') + 1,
        ]);

        $this->faq_category_id = $category->id;
        $this->categoryQuickAddModal = false;
        $this->newCategoryName = '';
        $this->success('Category added successfully.');
    }

    private function resetForm(): void
    {
        $this->editingFaq = null;
        $this->faq_category_id = FaqCategory::query()->orderBy('sort_order')->value('id');
        $this->question = '';
        $this->answer = '';
        $this->sort_order = 0;
        $this->is_active = true;
    }

    public function render(): View
    {
        $faqs = Faq::query()
            ->with('category')
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('question', 'like', '%'.$this->search.'%')
                        ->orWhere('answer', 'like', '%'.$this->search.'%')
                        ->orWhereHas('category', function (Builder $query): void {
                            $query->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->categoryFilter !== '', function (Builder $query): void {
                $query->where('faq_category_id', (int) $this->categoryFilter);
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.admin.faqs', [
            'faqs' => $faqs,
            'categoryOptions' => FaqCategory::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'totalFaqs' => Faq::count(),
            'activeFaqs' => Faq::where('is_active', true)->count(),
            'totalCategories' => FaqCategory::count(),
        ]);
    }
}
