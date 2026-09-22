<?php

namespace App\Livewire\User;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AuctionMarketplace extends Component
{
    use WithPagination;

    public function render(): View
    {
        $auctions = Auction::with(['product.images', 'traditionalAuction'])
            ->whereIn('status', [AuctionStatus::ACTIVE, AuctionStatus::PENDING, AuctionStatus::COMPLETED, AuctionStatus::ENDED_UNSOLD])
            ->latest()
            ->paginate(12);

        return view('livewire.user.auction', [
            'auctions' => $auctions,
        ]);
    }
}
