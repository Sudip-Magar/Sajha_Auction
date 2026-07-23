<?php

namespace App\Services;

use App\Events\AuctionBidPlaced;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuctionEngineService
{
    /**
     * Algorithm 2: Calculate Dynamic Step Minimum Increment \Delta(p)
     */
    public static function getStepIncrement(float $currentPrice, float $customMinIncrement = 0): float
    {
        if ($currentPrice < 1000) {
            $step = 50.0;
        } elseif ($currentPrice < 5000) {
            $step = 100.0;
        } elseif ($currentPrice < 20000) {
            $step = 250.0;
        } elseif ($currentPrice < 50000) {
            $step = 500.0;
        } else {
            $step = 1000.0;
        }

        return max($step, $customMinIncrement);
    }

    /**
     * Algorithm 2: Ascending Price with Minimum Increment and Proxy Bidding
     * Processes manual or proxy bid, evaluates runner-up proxy bids, updates standing price.
     */
    public static function processBid(
        Auction $auction,
        User $bidder,
        float $bidAmount,
        ?float $maxProxyAmount = null,
        ?string $ipAddress = null
    ): Bid {
        return DB::transaction(function () use ($auction, $bidder, $bidAmount, $maxProxyAmount, $ipAddress) {
            // Lock auction row for update to prevent concurrent race conditions
            $lockedAuction = Auction::where('id', $auction->id)->lockForUpdate()->firstOrFail();

            if (! $lockedAuction->isLive()) {
                throw new \Exception('This auction is not currently live for bidding.');
            }

            $currentPrice = (float) $lockedAuction->current_price;
            $customIncrement = (float) ($lockedAuction->traditionalAuction?->min_bid_increment ?? 0);
            $minIncrement = static::getStepIncrement($currentPrice, $customIncrement);
            $minNextBid = $currentPrice + $minIncrement;

            // If max proxy is set, ensure it's at least the entered bid amount
            $effectiveMaxProxy = $maxProxyAmount ? max($maxProxyAmount, $bidAmount) : $bidAmount;

            if ($bidAmount < $minNextBid && $effectiveMaxProxy < $minNextBid) {
                throw new \Exception('Your bid must be at least Rs. '.number_format($minNextBid, 2));
            }

            $isProxy = $maxProxyAmount !== null && $maxProxyAmount > $bidAmount;

            // Save new bid entry
            $bid = Bid::create([
                'auction_id' => $lockedAuction->id,
                'bidder_id' => $bidder->id,
                'bid_amount' => $bidAmount,
                'max_proxy_amount' => $effectiveMaxProxy,
                'is_proxy' => $isProxy,
                'ip_address' => $ipAddress ?? request()->ip() ?? '127.0.0.1',
                'placed_at' => now()->toTimeString(),
            ]);

            // Proxy Bidding Resolution (Algorithm 2)
            // Retrieve all distinct top proxy bidders for this auction
            $allBids = Bid::where('auction_id', $lockedAuction->id)
                ->orderBy('max_proxy_amount', 'desc')
                ->orderBy('created_at', 'asc')
                ->get();

            // Group by bidder to get each bidder's highest maximum
            $bidderMaxes = [];
            foreach ($allBids as $b) {
                $bId = $b->bidder_id;
                $maxVal = (float) ($b->max_proxy_amount ?? $b->bid_amount);
                if (! isset($bidderMaxes[$bId]) || $maxVal > $bidderMaxes[$bId]['max']) {
                    $bidderMaxes[$bId] = [
                        'bidder_id' => $bId,
                        'max' => $maxVal,
                        'bid' => $b,
                    ];
                }
            }

            // Sort bidders descending by max
            usort($bidderMaxes, fn ($a, $b) => $b['max'] <=> $a['max']);

            $newStandingPrice = $bidAmount;
            $automaticBid = null;

            if (count($bidderMaxes) === 1) {
                // Single bidder
                $startingBid = (float) ($lockedAuction->traditionalAuction?->starting_bid ?? 0);
                $newStandingPrice = max($bidAmount, $startingBid);
            } elseif (count($bidderMaxes) >= 2) {
                $m1 = $bidderMaxes[0]['max']; // Leader's max
                $m2 = $bidderMaxes[1]['max']; // Runner-up max

                $incrementForM2 = static::getStepIncrement($m2, $customIncrement);
                // Equation: p* = min(m1, m2 + \Delta(m2))
                $calculatedPrice = min($m1, $m2 + $incrementForM2);

                $newStandingPrice = max($newStandingPrice, $calculatedPrice);

                $leadingBid = $bidderMaxes[0]['bid'];

                // Record the proxy response so the bid history transparently
                // shows that automatic bidding defended the leader's position.
                if ($leadingBid->is_proxy && $newStandingPrice > $bidAmount) {
                    $automaticBid = Bid::create([
                        'auction_id' => $lockedAuction->id,
                        'bidder_id' => $leadingBid->bidder_id,
                        'bid_amount' => $newStandingPrice,
                        'max_proxy_amount' => $m1,
                        'is_proxy' => true,
                        'ip_address' => $ipAddress ?? request()->ip() ?? '127.0.0.1',
                        'placed_at' => now()->toTimeString(),
                    ]);
                }
            }

            $lockedAuction->update([
                'current_price' => $newStandingPrice,
                'total_bids' => $lockedAuction->bids()->count(),
            ]);

            // Broadcast only after the transaction commits, so Reverb listeners
            // always reload the new standing price and bid history.
            DB::afterCommit(function () use ($lockedAuction, $bid, $automaticBid): void {
                event(new AuctionBidPlaced($lockedAuction->fresh(['traditionalAuction']), $automaticBid ?? $bid));
            });

            return $bid;
        });
    }

    /**
     * Algorithm 1: Winner Determination with Reserve Price and Tie-Breaking
     */
    public static function determineWinner(Auction $auction): array
    {
        $reservePrice = (float) ($auction->traditionalAuction?->reserve_price ?? $auction->traditionalAuction?->starting_bid ?? 0);

        // A proxy maximum is the bidder's submitted bid for settlement purposes.
        // Keep the maximum for each bidder, and retain its earliest submission
        // timestamp to make equal top bids deterministic.
        $admissibleBids = $auction->bids()
            ->get()
            ->groupBy('bidder_id')
            ->map(function ($bids): Bid {
                $highestAmount = (float) $bids->max(
                    fn (Bid $bid): float => (float) ($bid->max_proxy_amount ?? $bid->bid_amount)
                );

                return $bids
                    ->filter(fn (Bid $bid): bool => (float) ($bid->max_proxy_amount ?? $bid->bid_amount) === $highestAmount)
                    ->sortBy('created_at')
                    ->first();
            })
            ->filter(fn (Bid $bid): bool => (float) ($bid->max_proxy_amount ?? $bid->bid_amount) >= $reservePrice)
            ->values();

        if ($admissibleBids->isEmpty()) {
            $reason = 'No admissible bids met or exceeded the reserve price of Rs. '.number_format($reservePrice, 2).'. Item remains unsold.';
            $auction->update([
                'winner_id' => null,
                'winning_price' => 0,
                'status' => 'ended_unsold',
                'settlement_reason' => $reason,
            ]);

            return [
                'has_winner' => false,
                'winner_id' => null,
                'winning_price' => 0,
                'reason' => $reason,
            ];
        }

        // Maximum bid amount b_{(1)}
        $maxBidAmount = (float) $admissibleBids->max(
            fn (Bid $bid): float => (float) ($bid->max_proxy_amount ?? $bid->bid_amount)
        );

        // Tied top bidders T = { i in V : b_i == b_{(1)} }
        $tiedTopBids = $admissibleBids->filter(
            fn (Bid $bid): bool => (float) ($bid->max_proxy_amount ?? $bid->bid_amount) === $maxBidAmount
        );

        // Tie-breaking rule: Earliest submission (smallest timestamp) wins: w = argmin_{i in T} t_i
        $winningBid = $tiedTopBids->sortBy('created_at')->first();
        $isTie = $tiedTopBids->count() > 1;

        $reason = $isTie
            ? 'Winning bid of Rs. '.number_format($maxBidAmount, 2).' awarded via earliest-timestamp tie-breaking rule among '.$tiedTopBids->count().' tied top bidders.'
            : 'Highest admissible bid of Rs. '.number_format($maxBidAmount, 2).' above reserve price of Rs. '.number_format($reservePrice, 2).'.';

        $auction->update([
            'winner_id' => $winningBid->bidder_id,
            'winning_price' => $maxBidAmount,
            'status' => 'completed',
            'settlement_reason' => $reason,
        ]);

        if ($auction->product) {
            $auction->product->update(['status' => 'sold']);
            $auction->product->logTimeline(
                'auction_ended',
                'Auction Finalized & Won',
                $reason,
                $winningBid->bidder
            );
        }

        return [
            'has_winner' => true,
            'winner' => $winningBid->bidder,
            'winner_id' => $winningBid->bidder_id,
            'winning_price' => $maxBidAmount,
            'winning_bid' => $winningBid,
            'reason' => $reason,
        ];
    }

    /**
     * Helper to auto-settle expired auctions using Algorithm 1.
     */
    public static function checkAndFinalizeIfExpired(Auction $auction): bool
    {
        if ($auction->status === 'active' && $auction->end_time && $auction->end_time <= now()) {
            static::determineWinner($auction);

            return true;
        }

        return false;
    }

    /**
     * Algorithm 3 (b): Revenue-Maximizing Myerson Optimal Reserve Price
     * r* = (Seller Valuation + Starting Bid) / 2
     */
    public static function calculateOptimalReserve(float $sellerValuation, float $startingBid = 0): float
    {
        if ($sellerValuation <= 0 && $startingBid <= 0) {
            return 0.0;
        }

        $base = max($sellerValuation, $startingBid);
        $minVal = min($sellerValuation, $startingBid);

        // Optimal reserve extracts extra expected revenue even if item doesn't sell occasionally
        $optimal = ($base + $minVal) / 2;

        return round(max($optimal, $startingBid), 2);
    }

    /**
     * Algorithm 3 (a): Symmetric Bayes-Nash Equilibrium Bidding Strategy
     * \beta(v) = ((n - 1) / n) * v
     */
    public static function calculateEquilibriumBid(float $privateValuation, int $numberOfBidders = 2): float
    {
        if ($privateValuation <= 0) {
            return 0.0;
        }

        $n = max(2, $numberOfBidders);
        $equilibrium = (($n - 1) / $n) * $privateValuation;

        return round($equilibrium, 2);
    }
}
