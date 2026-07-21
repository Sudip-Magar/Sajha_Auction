<?php

namespace App\Livewire\User;

use App\Enums\ProductNegotiability;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SearchProduct extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $condition = '';

    #[Url(except: '')]
    public string $minPrice = '';

    #[Url(except: '')]
    public string $maxPrice = '';

    #[Url(except: 'any')]
    public string $negotiable = ProductNegotiability::ANY->value;

    /**
     * @return array<string, string>
     */
    public function conditions(): array
    {
        return [
            'new' => 'New',
            'like-new' => 'Like New',
            'used' => 'Used',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedCondition(): void
    {
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function updatedNegotiable(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'category', 'condition', 'minPrice', 'maxPrice');
        $this->negotiable = ProductNegotiability::ANY->value;
        $this->resetPage();
    }

    public function render(): View
    {
        $search = trim($this->search);
        $conditionSearch = str($search)->lower()->replace(' ', '-')->toString();
        $minPrice = is_numeric($this->minPrice) ? (float) $this->minPrice : null;
        $maxPrice = is_numeric($this->maxPrice) ? (float) $this->maxPrice : null;

        $products = Product::with(['auction.traditionalAuction', 'category', 'images', 'user'])
            ->where('is_approved', true)
            ->where('status', 'active')
            ->when($search !== '', function (Builder $query) use ($search, $conditionSearch): void {
                $query->where(function (Builder $query) use ($search, $conditionSearch): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('condition', 'like', '%'.$search.'%')
                        ->orWhere('condition', 'like', '%'.$conditionSearch.'%')
                        ->orWhereHas('category', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($this->category !== '', function (Builder $query): void {
                $query->whereHas('category', function (Builder $query): void {
                    if (is_numeric($this->category)) {
                        $query->whereKey((int) $this->category)
                            ->orWhere('category_id', (int) $this->category);

                        return;
                    }

                    $query->where('slug', $this->category)
                        ->orWhere('name', 'like', '%'.$this->category.'%');
                });
            })
            ->when($this->condition !== '', function (Builder $query): void {
                $query->where('condition', $this->condition);
            })
            ->when($minPrice !== null || $maxPrice !== null, function (Builder $query) use ($minPrice, $maxPrice): void {
                $query->where(function (Builder $query) use ($minPrice, $maxPrice): void {
                    $query
                        ->where(function (Builder $query) use ($minPrice, $maxPrice): void {
                            $query->whereNotNull('sale_price');
                            $this->applyPriceRange($query, 'sale_price', $minPrice, $maxPrice);
                        })
                        ->orWhereHas('auction', function (Builder $query) use ($minPrice, $maxPrice): void {
                            $query->where(function (Builder $query) use ($minPrice, $maxPrice): void {
                                $this->applyPriceRange($query, 'current_price', $minPrice, $maxPrice);
                            })->orWhereHas('traditionalAuction', function (Builder $query) use ($minPrice, $maxPrice): void {
                                $this->applyPriceRange($query, 'starting_bid', $minPrice, $maxPrice);
                            });
                        });
                });
            })
            ->when($this->negotiable !== ProductNegotiability::ANY->value, function (Builder $query): void {
                $query->where('negotiable', $this->negotiable);
            })
            ->latest()
            ->paginate(12);

        return view('livewire.user.search-product', [
            'products' => $products,
            'categories' => Category::query()
                ->with(['children' => function (HasMany $query): void {
                    $query->where('status', 'active')
                        ->orderBy('sort_order')
                        ->orderBy('name');
                }])
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'conditions' => $this->conditions(),
            'negotiabilityOptions' => ProductNegotiability::cases(),
        ]);
    }

    private function applyPriceRange(Builder $query, string $column, ?float $minPrice, ?float $maxPrice): void
    {
        if ($minPrice !== null) {
            $query->where($column, '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where($column, '<=', $maxPrice);
        }
    }
}
