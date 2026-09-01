<?php

namespace App\Livewire\User;

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
            ->whereIn('status', ['active', 'pending', 'completed', 'ended_unsold'])
            ->latest()
            ->paginate(12);

        return view('livewire.user.auction', [
            'auctions' => $auctions,
        ]);
    }
}
