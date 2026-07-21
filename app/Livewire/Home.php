<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Home extends Component
{
    use Toast;

    public $categorySearch = '';

    public function toggleBookmark(int $productId): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        $productExists = Product::query()
            ->whereKey($productId)
            ->where('is_approved', true)
            ->where('status', 'active')
            ->exists();

        if (! $productExists) {
            $this->error('This product is not available.');

            return;
        }

        $bookmark = $user->bookmarks()
            ->where('product_id', $productId)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            $this->success('Product removed from bookmarks.');

            return;
        }

        $user->bookmarks()->create([
            'product_id' => $productId,
        ]);

        $this->success('Product added to bookmarks.');
    }

    public function render(): View
    {
        $productsQuery = Product::with(['auction.traditionalAuction', 'category', 'images', 'user'])
            ->where('is_approved', true)
            ->where('status', 'active');

        return view('livewire.home', [
            'bannerSlides' => $this->bannerSlides(),
            'categories' => Category::query()
                ->with(['children' => function ($query): void {
                    $query->where('status', 'active')
                        ->orderBy('sort_order')
                        ->orderBy('name');
                }])
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->take(14)
                ->get(),
            'popularSearches' => [
                'Mobile phones',
                'Bikes',
                'Laptops',
                'Property',
                'Furniture',
                'Services',
            ],
            'bookmarkedProductIds' => Auth::user()
                ? Auth::user()->bookmarks()->pluck('product_id')->all()
                : [],
            'featuredProducts' => (clone $productsQuery)
                ->where('is_featured', true)
                ->latest()
                ->take(8)
                ->get(),
            'trendingProducts' => (clone $productsQuery)
                ->where(function ($query): void {
                    $query->where('is_trending', true)
                        ->orWhere('views_count', '>', 0);
                })
                ->orderByDesc('is_trending')
                ->orderByDesc('views_count')
                ->latest()
                ->take(8)
                ->get(),
            'latestProducts' => $productsQuery
                ->latest()
                ->paginate(12),
        ]);
    }

    /**
     * @return array<int, array{eyebrow: string, title: string, copy: string, cta: string, icon: string, gradient: string}>
     */
    private function bannerSlides(): array
    {
        return [
            [
                'eyebrow' => 'Verified marketplace',
                'title' => 'Buy and auction locally across Nepal',
                'copy' => 'Browse approved listings, inspect seller details, and join active auctions from one clean feed.',
                'cta' => 'Explore listings',
                'icon' => 'o-shield-check',
                'gradient' => 'linear-gradient(115deg, #0C8FE8 0%, #28C2E0 100%)',
            ],
            [
                'eyebrow' => 'Seller tools',
                'title' => 'Post products and reach serious buyers',
                'copy' => 'Create fixed-price listings or auction products with approval, visibility, and safer account controls.',
                'cta' => 'Post for free',
                'icon' => 'o-megaphone',
                'gradient' => 'linear-gradient(115deg, #0F9F6E 0%, #20B6A8 100%)',
            ],
            [
                'eyebrow' => 'Trending now',
                'title' => 'Catch popular deals before they move',
                'copy' => 'Featured and trending products stay easy to scan with prices, condition, seller, and location.',
                'cta' => 'View trending',
                'icon' => 'o-arrow-trending-up',
                'gradient' => 'linear-gradient(115deg, #F59E0B 0%, #F97316 100%)',
            ],
        ];
    }
}
