<?php

namespace App\Services;

use App\Events\AuctionBidPlaced;
use App\Mail\AuctionWonMail;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AuctionWonNotification;
use App\Notifications\OutbidNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            if (! User::whereKey($bidder->id)->where('is_auction_allowed', true)->exists()) {
                throw new \Exception('Your account is not approved for live auction bidding.');
            }

            // Lock auction row for update to prevent concurrent race conditions
            $lockedAuction = Auction::where('id', $auction->id)->lockForUpdate()->firstOrFail();

            if (! $lockedAuction->isLive()) {
                throw new \Exception('This auction is not currently live for bidding.');
            }

            $currentPrice = (float) $lockedAuction->current_price;
            $customIncrement = (float) ($lockedAuction->traditionalAuction?->min_bid_increment ?? 0);
            $minIncrement = static::getStepIncrement($currentPrice, $customIncrement);
            $minNextBid = $currentPrice + $minIncrement;

            if ($bidAmount < $minNextBid) {
                throw new \Exception('Your bid must be at least Rs. '.number_format($minNextBid, 2));
            }

            $effectiveMaxProxy = max($maxProxyAmount ?? $bidAmount, $bidAmount);
            $isProxy = $effectiveMaxProxy > $bidAmount;

            // Snapshot who was leading before this bid is recorded, so we can
            // tell them they've been outbid once the new leader is resolved.
            $previousLeaderId = static::rankBidderMaxes(
                Bid::where('auction_id', $lockedAuction->id)->orderBy('created_at')->orderBy('id')->get()
            )[0]['bidder_id'] ?? null;

            // Save new bid entry
            $bid = Bid::create([
                'auction_id' => $lockedAuction->id,
                'bidder_id' => $bidder->id,
                'bid_amount' => $bidAmount,
                'max_proxy_amount' => $isProxy ? $effectiveMaxProxy : null,
                'is_proxy' => $isProxy,
                'ip_address' => $ipAddress ?? request()->ip() ?? '127.0.0.1',
                'placed_at' => now()->toTimeString(),
            ]);

            // Proxy Bidding Resolution (Algorithm 2)
            // Retrieve all distinct top proxy bidders for this auction
            $allBids = Bid::where('auction_id', $lockedAuction->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $bidderMaxes = static::rankBidderMaxes($allBids);

            $newStandingPrice = max($currentPrice, $bidAmount);
            $automaticBid = null;

            if (count($bidderMaxes) === 1) {
                // Single bidder
                $startingBid = (float) ($lockedAuction->traditionalAuction?->starting_bid ?? 0);
                $newStandingPrice = max($newStandingPrice, $startingBid);
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
                // This must fire even when the leader IS the bidder who just
                // placed this request (their own proxy ceiling beat everyone
                // else): otherwise current_price advances to the resolved
                // price while no Bid row ever records it, and Algorithm 1
                // (determineWinner, which settles purely off Bid.bid_amount)
                // can under-charge the winner relative to what was displayed live.
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

            // Anti-sniping: a bid landing inside the closing `timer_reset_seconds`
            // window of the effective deadline pushes that deadline out by the
            // same window again, so the countdown never lets someone win purely
            // by bidding in the last second with no chance for others to respond.
            $effectiveEndTime = $lockedAuction->extended_end_time ?? $lockedAuction->end_time;
            $resetSeconds = (int) ($lockedAuction->traditionalAuction?->timer_reset_seconds ?? 0);
            $newExtendedEndTime = $lockedAuction->extended_end_time;

            if ($resetSeconds > 0 && $effectiveEndTime) {
                $secondsRemaining = $effectiveEndTime->getTimestamp() - now()->getTimestamp();

                if ($secondsRemaining <= $resetSeconds) {
                    $candidateEndTime = now()->addSeconds($resetSeconds);

                    if ($candidateEndTime->gt($effectiveEndTime)) {
                        $newExtendedEndTime = $candidateEndTime;
                    }
                }
            }

            $lockedAuction->update([
                'current_price' => $newStandingPrice,
                'total_bids' => $lockedAuction->bids()->count(),
                'extended_end_time' => $newExtendedEndTime,
            ]);

            $newLeaderId = $bidderMaxes[0]['bidder_id'] ?? null;

            // Broadcast only after the transaction commits, so Reverb listeners
            // always reload the new standing price and bid history.
            DB::afterCommit(function () use ($lockedAuction, $bid, $automaticBid, $previousLeaderId, $newLeaderId, $newStandingPrice): void {
                event(new AuctionBidPlaced($lockedAuction->fresh(['traditionalAuction']), $automaticBid ?? $bid));

                if ($previousLeaderId !== null && $newLeaderId !== null && $previousLeaderId !== $newLeaderId) {
                    $previousLeader = User::find($previousLeaderId);

                    if ($previousLeader) {
                        try {
                            $previousLeader->notify(new OutbidNotification($lockedAuction, $newStandingPrice));
                        } catch (\Throwable $exception) {
                            Log::warning('Outbid notification could not be delivered.', [
                                'auction_id' => $lockedAuction->id,
                                'previous_leader_id' => $previousLeaderId,
                                'exception' => $exception->getMessage(),
                            ]);
                        }
                    }
                }
            });

            return $bid;
        });
    }

    /**
     * Ranks each bidder's highest submitted max (proxy ceiling, or plain bid
     * amount for a non-proxy bid) across the given bids, most competitive
     * first — used both to resolve the standing price (Algorithm 2) and to
     * detect the previous leader when dispatching outbid notifications.
     *
     * @param  Collection<int, Bid>  $bids
     * @return array<int, array{bidder_id: int, max: float, bid: Bid, submitted_at: \Illuminate\Support\Carbon}>
     */
    private static function rankBidderMaxes(Collection $bids): array
    {
        $bidderMaxes = [];
        foreach ($bids as $b) {
            $bidderId = $b->bidder_id;
            $maxVal = (float) ($b->max_proxy_amount ?? $b->bid_amount);
            if (! isset($bidderMaxes[$bidderId]) || $maxVal > $bidderMaxes[$bidderId]['max']) {
                $bidderMaxes[$bidderId] = [
                    'bidder_id' => $bidderId,
                    'max' => $maxVal,
                    'bid' => $b,
                    'submitted_at' => $b->created_at,
                ];
            }
        }

        // Sort by maximum, then use the earliest maximum registration to
        // resolve equal proxy limits deterministically.
        usort($bidderMaxes, function (array $first, array $second): int {
            $maximumComparison = $second['max'] <=> $first['max'];

            if ($maximumComparison !== 0) {
                return $maximumComparison;
            }

            return $first['submitted_at']->getTimestamp() <=> $second['submitted_at']->getTimestamp()
                ?: $first['bid']->id <=> $second['bid']->id;
        });

        return $bidderMaxes;
    }

    /**
     * Algorithm 1: Winner Determination with Reserve Price and Tie-Breaking
     */
    public static function determineWinner(Auction $auction): array
    {
        if ($auction->status !== 'active') {
            return [
                'has_winner' => $auction->winner_id !== null,
                'winner_id' => $auction->winner_id,
                'winning_price' => (float) ($auction->winning_price ?? 0),
                'reason' => $auction->settlement_reason ?? 'This auction has already been settled.',
            ];
        }

        $reservePrice = (float) ($auction->traditionalAuction?->reserve_price ?? 0);

        // Algorithm 1 is first-price: settlement uses the visible submitted
        // amount, never a bidder's secret proxy ceiling.
        $admissibleBids = $auction->bids()
            ->where('bid_amount', '>=', $reservePrice)
            ->get();

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
        $maxBidAmount = (float) $admissibleBids->max('bid_amount');

        // Tied top bidders T = { i in V : b_i == b_{(1)} }
        $tiedTopBids = $admissibleBids->filter(
            fn (Bid $bid): bool => (float) $bid->bid_amount === $maxBidAmount
        );

        // Tie-breaking rule: Earliest submission (smallest timestamp) wins: w = argmin_{i in T} t_i
        $winningBid = $tiedTopBids->sortBy([
            ['created_at', 'asc'],
            ['id', 'asc'],
        ])->first();
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

        $winningBid->loadMissing('bidder');
        $auction->loadMissing(['product', 'winner']);

        if ($auction->product && $winningBid->bidder) {
            $order = Order::create([
                'buyer_id' => $winningBid->bidder_id,
                'seller_id' => $auction->product->seller_id,
                'status' => 'pending',
                'total_amount' => $maxBidAmount,
                'payment_method' => 'cash_on_meetup',
                'payment_status' => 'pending',
                'handover_type' => 'meetup',
                'meetup_location' => $auction->product->meetup_location ?: $auction->product->location,
                'buyer_phone' => $winningBid->bidder->phone,
                'notes' => "Created automatically after winning auction #{$auction->id}.",
            ]);

            $order->items()->create([
                'product_id' => $auction->product->id,
                'price' => $maxBidAmount,
                'quantity' => 1,
                'subtotal' => $maxBidAmount,
            ]);

            try {
                $winningBid->bidder->notify(new AuctionWonNotification($auction));
                Mail::to($winningBid->bidder->email)->send(new AuctionWonMail($auction));
            } catch (\Throwable $exception) {
                Log::warning('Auction winner notification could not be delivered.', [
                    'auction_id' => $auction->id,
                    'winner_id' => $winningBid->bidder_id,
                    'exception' => $exception->getMessage(),
                ]);
            }
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
        if ($auction->status === 'active' && $auction->effective_end_time && $auction->effective_end_time <= now()) {
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
