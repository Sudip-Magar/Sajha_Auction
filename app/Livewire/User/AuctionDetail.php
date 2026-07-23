<?php

namespace App\Livewire\User;

use App\Models\Auction;
use App\Services\AuctionEngineService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
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

    public bool $isProxyMode = false;

    public ?float $maxProxyAmount = null;

    // Private valuation used to generate an optional suggested bid.
    public ?float $privateValuation = null;

    public ?float $recommendedBid = null;

    public function getListeners(): array
    {
        return [
            "echo:auctions.{$this->auction->id},AuctionBidPlaced" => 'refreshAuctionState',
            "echo:auctions.{$this->auction->id},.auction.bid.placed" => 'refreshAuctionState',
        ];
    }

    public function mount(Auction $auction): void
    {
        $this->auction = $auction;
        $this->refreshAuctionData();

        // Auto-check whether an expired auction needs settlement.
        AuctionEngineService::checkAndFinalizeIfExpired($this->auction);
        $this->refreshAuctionData();
    }

    public function refreshAuctionState(): void
    {
        $this->refreshAuctionData();
    }

    private function refreshAuctionData(): void
    {
        $this->auction = Auction::with([
            'product.images',
            'product.category',
            'product.user',
            'traditionalAuction',
            'winner',
            'bids' => fn ($q) => $q->with('bidder')->latest()->take(20),
        ])->findOrFail($this->auction->id);

        if (! $this->isProxyMode) {
            $this->bidAmount = (float) $this->auction->getMinNextBid();
        }

        $this->calculateEquilibriumRecommendation();
    }

    public function calculateEquilibriumRecommendation(): void
    {
        if (! $this->privateValuation || $this->privateValuation <= 0) {
            $this->recommendedBid = null;

            return;
        }

        $activeBiddersCount = max(2, $this->auction->bids()->distinct('bidder_id')->count('bidder_id'));
        $this->recommendedBid = AuctionEngineService::calculateEquilibriumBid(
            (float) $this->privateValuation,
            $activeBiddersCount
        );
    }

    public function applyRecommendedBid(): void
    {
        if ($this->recommendedBid && $this->recommendedBid >= $this->auction->getMinNextBid()) {
            $this->bidAmount = (float) $this->recommendedBid;
            $this->success('Applied suggested bid: Rs. '.number_format($this->recommendedBid, 2));
        } elseif ($this->recommendedBid) {
            $this->warning('The suggested bid (Rs. '.number_format($this->recommendedBid, 2).') is below the minimum required next bid of Rs. '.number_format($this->auction->getMinNextBid(), 2));
        }
    }

    public function toggleProxyMode(): void
    {
        $this->isProxyMode = ! $this->isProxyMode;
        if ($this->isProxyMode && ! $this->maxProxyAmount) {
            $this->maxProxyAmount = (float) ($this->auction->getMinNextBid() * 1.1);
        }
    }

    public function placeBid(): void
    {
        if (! Auth::check()) {
            $this->error('Please login to place a bid.');
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        $user = Auth::user();

        if ((int) $this->auction->product?->seller_id === (int) $user->id) {
            $this->error('You cannot bid on your own product listing.');

            return;
        }

        // Auto check expiration before placing bid
        if (AuctionEngineService::checkAndFinalizeIfExpired($this->auction)) {
            $this->error('This auction has just ended.');
            $this->refreshAuctionData();

            return;
        }

        if (! $this->auction->isLive()) {
            $this->error('This auction is not currently live for bidding.');

            return;
        }

        $minNextBid = $this->auction->getMinNextBid();

        if (! $this->isProxyMode && $this->bidAmount < $minNextBid) {
            $this->error('Your bid must be at least Rs. '.number_format($minNextBid, 2));

            return;
        }

        if ($this->isProxyMode) {
            if (! $this->maxProxyAmount || $this->maxProxyAmount < $minNextBid) {
                $this->error('Your max proxy limit must be at least Rs. '.number_format($minNextBid, 2));

                return;
            }

            $this->bidAmount = $minNextBid;
        }

        try {
            AuctionEngineService::processBid(
                $this->auction,
                $user,
                $this->bidAmount,
                $this->isProxyMode ? (float) $this->maxProxyAmount : null,
                request()->ip()
            );

            $this->success($this->isProxyMode ? 'Proxy bid registered successfully!' : 'Your bid has been placed successfully!');
            $this->isProxyMode = false;
            $this->maxProxyAmount = null;
            $this->refreshAuctionData();

        } catch (Throwable $e) {
            $this->error($e->getMessage() ?: 'An error occurred while placing your bid.');
        }
    }

    public function setQuickBid(int $addAmount): void
    {
        $minNextBid = $this->auction->getMinNextBid();
        $this->bidAmount = (float) ($minNextBid + $addAmount);
    }

    public function render(): View
    {
        AuctionEngineService::checkAndFinalizeIfExpired($this->auction);

        return view('livewire.user.auction-detail', [
            'recentBids' => $this->auction->bids()->with('bidder')->latest()->take(10)->get(),
            'totalBidders' => $this->auction->bids()->distinct('bidder_id')->count('bidder_id'),
            'stepIncrement' => $this->auction->getStepIncrement(),
        ]);
    }
}
