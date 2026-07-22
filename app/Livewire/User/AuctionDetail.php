<?php

namespace App\Livewire\User;

use App\Events\AuctionBidPlaced;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;
use Throwable;

#[Layout('layouts.app')]
class AuctionDetail extends Component
{
    use Toast;

    public Auction $auction;

    public float $bidAmount = 0;

    public function getListeners(): array
    {
        return [
            'echo:auctions.'.$this->auction->id.',AuctionBidPlaced' => '$refresh',
        ];
    }

    public function mount(Auction $auction): void
    {
        $this->auction = $auction->load(['product.images', 'product.category', 'traditionalAuction', 'bids.bidder']);
        $this->bidAmount = (float) $this->auction->getMinNextBid();
    }

    public function placeBid(): void
    {
        if (! Auth::check()) {
            $this->error('Please login to place a bid.');
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        if (! Auth::user()->is_auction_allowed) {
            $this->error('Your account is not approved for bidding. Please submit your documents first.');
            $this->redirect(route('user.join-auction'), navigate: true);

            return;
        }

        if ((int) $this->auction->product?->seller_id === (int) Auth::id()) {
            $this->error('You cannot bid on your own product listing.');

            return;
        }

        if (! $this->auction->isLive()) {
            $this->error('This auction is not currently live.');

            return;
        }

        $minNextBid = $this->auction->getMinNextBid();
        if ($this->bidAmount < $minNextBid) {
            $this->error('Your bid must be at least Rs. '.number_format($minNextBid));

            return;
        }

        try {
            DB::transaction(function () {
                // Re-fetch auction with lock to prevent race conditions
                $auction = Auction::where('id', $this->auction->id)->lockForUpdate()->first();

                if ($this->bidAmount < $auction->getMinNextBid()) {
                    throw new \Exception('Someone else just placed a higher bid.');
                }

                $bid = Bid::create([
                    'auction_id' => $auction->id,
                    'bidder_id' => Auth::id(),
                    'bid_amount' => $this->bidAmount,
                    'ip_address' => request()->ip(),
                    'placed_at' => now()->toTimeString(),
                ]);

                $auction->update([
                    'current_price' => $this->bidAmount,
                    'total_bids' => $auction->total_bids + 1,
                ]);

                $this->auction = $auction->load(['product.images', 'product.category', 'traditionalAuction', 'bids.bidder']);

                event(new AuctionBidPlaced($auction, $bid));
            });

            $this->success('Your bid has been placed successfully!');
            $this->bidAmount = (float) $this->auction->getMinNextBid();

        } catch (Throwable $e) {
            Log::error('Bidding Error: '.$e->getMessage());
            $this->error($e->getMessage() ?: 'An error occurred while placing your bid.');
        }
    }

    public function setQuickBid(int $addAmount): void
    {
        $this->bidAmount = (float) ($this->auction->getMinNextBid() + $addAmount);
    }

    public function render(): View
    {
        return view('livewire.user.auction-detail');
    }
}
