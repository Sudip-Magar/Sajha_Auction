<?php

namespace App\Livewire\User;

use App\Enums\ProductApprovalStatus;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function getListeners(): array
    {
        $userId = Auth::id();

        if (! $userId) {
            return [
                'userNotificationReceived' => '$refresh',
            ];
        }

        return [
            'userNotificationReceived' => '$refresh',
            "echo-notification:App.Models.User.{$userId}" => '$refresh',
        ];
    }

    public function mount(): void
    {
        if (! Auth::user()?->is_seller) {
            $this->redirect(route('home'), navigate: true);
        }
    }

    public function render(): View
    {
        if (! Auth::user()?->is_seller) {
            $this->redirect(route('home'), navigate: true);
        }

        $userId = Auth::id();

        $products = Product::query()->where('seller_id', $userId);

        return view('livewire.user.dashboard', [
            'totalProducts' => (clone $products)->count(),
            'activeProducts' => (clone $products)->where('status', 'active')->count(),
            // Anything not yet approved - pending review, sent back for
            // correction, or rejected - all need the seller's attention.
            // Counting PENDING alone used to silently drop correction and
            // rejected listings from this stat.
            'needsAttentionProducts' => (clone $products)->where('approval_status', '!=', ProductApprovalStatus::APPROVED)->count(),
            'auctionProducts' => (clone $products)->where('listing_type', 'auction')->count(),
            'recentProducts' => (clone $products)->with(['category', 'images'])->latest()->take(5)->get(),
            'user' => Auth::user(),
        ]);
    }
}
