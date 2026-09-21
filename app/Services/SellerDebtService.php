<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\SellerDebt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records what a seller owes the platform after an upheld buyer complaint and
 * recovers it from money the admin is about to pay that seller.
 *
 * Only admin-held money (currently the seller's share of a forfeited deposit)
 * can be netted. Cash the seller collects at a meetup never passes through
 * the platform, so it cannot be deducted.
 */
class SellerDebtService
{
    public static function penaltyFor(Order $order): float
    {
        return round((float) $order->total_amount * (float) config('services.esewa.seller_penalty_percentage', 50) / 100, 2);
    }

    /**
     * Record the seller's debt for an upheld complaint.
     */
    public static function record(Order $order, string $reason): SellerDebt
    {
        $amount = self::penaltyFor($order);

        return DB::transaction(function () use ($order, $reason, $amount): SellerDebt {
            $debt = SellerDebt::create([
                'seller_id' => $order->seller_id,
                'related_order_id' => $order->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => SellerDebt::STATUS_OUTSTANDING,
            ]);

            PaymentTransaction::create([
                'order_id' => $order->id,
                'type' => PaymentTransaction::TYPE_DEBT_RECORDED,
                'amount' => $amount,
                'status' => 'completed',
                'party' => 'seller',
                'notes' => $reason,
            ]);

            return $debt;
        });
    }

    /**
     * Total the seller still owes.
     */
    public static function outstandingFor(User $seller): float
    {
        return round((float) SellerDebt::where('seller_id', $seller->id)
            ->where('status', SellerDebt::STATUS_OUTSTANDING)
            ->get()
            ->sum(fn (SellerDebt $debt): float => $debt->remaining), 2);
    }

    /**
     * Deduct outstanding debt (oldest first) from a payout about to be made to
     * the seller. Returns how much was deducted; the seller receives the rest.
     * If the payout does not cover the debt the seller gets nothing this round
     * and the remainder carries to the next payout.
     */
    public static function recoverFromPayout(User $seller, float $payoutAmount, Order $payoutOrder): float
    {
        return DB::transaction(function () use ($seller, $payoutAmount, $payoutOrder): float {
            $debts = SellerDebt::where('seller_id', $seller->id)
                ->where('status', SellerDebt::STATUS_OUTSTANDING)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $available = round($payoutAmount, 2);
            $deducted = 0.0;

            foreach ($debts as $debt) {
                if ($available <= 0) {
                    break;
                }

                $take = min($available, $debt->remaining);
                $newRecovered = round($debt->recovered_amount + $take, 2);
                $isCleared = $newRecovered >= $debt->amount;

                $debt->update([
                    'recovered_amount' => $newRecovered,
                    'status' => $isCleared ? SellerDebt::STATUS_RECOVERED : SellerDebt::STATUS_OUTSTANDING,
                    'recovered_at' => $isCleared ? now() : null,
                ]);

                PaymentTransaction::create([
                    'order_id' => $debt->related_order_id,
                    'type' => PaymentTransaction::TYPE_DEBT_RECOVERED,
                    'amount' => $take,
                    'status' => 'completed',
                    'party' => 'seller',
                    'notes' => "Deducted from the seller's payout on order #{$payoutOrder->order_number}.",
                ]);

                $available = round($available - $take, 2);
                $deducted = round($deducted + $take, 2);
            }

            return $deducted;
        });
    }
}
