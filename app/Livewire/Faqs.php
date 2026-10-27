<?php

namespace App\Livewire;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Faqs extends Component
{
    use WithPagination;

    public string $categoryFilter = '';

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $faqs = Faq::query()
            ->with('category')
            ->where('is_active', true)
            ->when($this->categoryFilter !== '', function ($query): void {
                $query->where('faq_category_id', (int) $this->categoryFilter);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(15);

        return view('livewire.faqs', [
            'faqs' => $faqs,
            'categories' => FaqCategory::query()
                ->whereHas('faqs', fn ($query) => $query->where('is_active', true))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
