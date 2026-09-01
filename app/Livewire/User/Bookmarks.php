<?php

namespace App\Livewire\User;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Bookmarks extends Component
{
    use Toast, WithPagination;

    public function removeBookmark(int $productId): void
    {
        Auth::user()
            ?->bookmarkedProducts()
            ->detach($productId);

        $this->success('Product removed from bookmarks.');
    }

    public function render(): View
    {
        return view('livewire.user.bookmarks', [
            'products' => Auth::user()
                ->bookmarkedProducts()
                ->with(['auction.traditionalAuction', 'category', 'images', 'user'])
                ->where('is_approved', true)
                ->where('status', 'active')
                ->orderByDesc('bookmarks.created_at')
                ->paginate(12),
        ]);
    }
}
