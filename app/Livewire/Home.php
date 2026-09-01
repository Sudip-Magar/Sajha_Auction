<?php

namespace App\Livewire;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Wishlist;
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

    public function toggleWishlist(int $productId): void
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

        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            $user->bookmarks()->where('product_id', $productId)->delete();
            $this->success('Product removed from wishlist.');
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);
            $user->bookmarks()->firstOrCreate(['product_id' => $productId]);
            $this->success('Product added to wishlist!');
        }

        $this->dispatch('wishlistUpdated');
    }

    public function toggleBookmark(int $productId): void
    {
        $this->toggleWishlist($productId);
    }

    public function addToCart(int $productId): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->warning('Please sign in to add items to cart.');
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        $product = Product::find($productId);
        if (! $product || ! $product->isDirectSell()) {
            $this->error('Item is not available for direct buy.');

            return;
        }

        if ((int) $product->seller_id === (int) $user->id) {
            $this->error('You cannot add your own product to cart.');

            return;
        }

        $cartItem = CartItem::firstOrCreate(
            ['user_id' => $user->id, 'product_id' => $productId],
            ['quantity' => 1]
        );

        if (! $cartItem->wasRecentlyCreated) {
            $cartItem->increment('quantity');
        }

        $this->success('Product added to cart!');
        $this->dispatch('cartUpdated');
    }

    public function render(): View
    {
        $productsQuery = Product::with(['auction.traditionalAuction', 'category', 'images', 'user'])
            ->where('is_approved', true)
            ->where(function ($query): void {
                $query->where('status', 'active')
                    ->orWhereHas('auction', fn ($auctionQuery) => $auctionQuery->where('end_time', '<=', now()));
            });

        $user = Auth::user();
        $wishlistedIds = $user
            ? Wishlist::where('user_id', $user->id)->pluck('product_id')->all()
            : [];

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
            'bookmarkedProductIds' => $wishlistedIds,
            'wishlistedProductIds' => $wishlistedIds,
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
                'eyebrow' => 'Second-Hand & Auction Marketplace',
                'title' => 'Buy, Sell & Auction Second-Hand Items in Nepal',
                'copy' => 'Browse approved listings with verified meetup places, inspect seller details, and order directly or join live auctions.',
                'cta' => 'Explore Marketplace',
                'icon' => 'o-shield-check',
                'gradient' => 'linear-gradient(115deg, #1F6F5F 0%, #2FA084 100%)',
            ],
            [
                'eyebrow' => 'Seller Tools',
                'title' => 'Post Second-Hand Products & Set Meetup Places',
                'copy' => 'List your second-hand products, set prices and meetup places, and manage buyer orders effortlessly.',
                'cta' => 'Upload Product',
                'icon' => 'o-megaphone',
                'gradient' => 'linear-gradient(115deg, #0F9F6E 0%, #20B6A8 100%)',
            ],
            [
                'eyebrow' => 'Trending Deals',
                'title' => 'Catch Popular Direct-Sell & Auction Deals',
                'copy' => 'Featured products, condition reports, meetup places, and pricing details updated live.',
                'cta' => 'View Trending',
                'icon' => 'o-arrow-trending-up',
                'gradient' => 'linear-gradient(115deg, #F59E0B 0%, #F97316 100%)',
            ],
        ];
    }
}
