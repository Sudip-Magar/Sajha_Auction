<?php

namespace App\Services;

use App\Enums\PaymentTransactionParty;
use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use App\Enums\SellerDebtStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\SellerDebt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Recovers a seller's outstanding debt from money the admin is about to pay
 * them.
 *
 * Only admin-held money (currently the seller's share of a forfeited deposit)
 * can be netted. Cash the seller collects at a meetup never passes through
 * the platform, so it cannot be deducted.
 *
 * Debt used to be recorded here too (for an upheld buyer complaint via
 * record()/penaltyFor()), but that's been fully superseded by
 * DamagePenaltyService's direct seller-pays-admin penalty flow - nothing
 * creates a SellerDebt row anymore. This service only recovers debt that
 * already exists.
 */
class SellerDebtService
{
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
                ->where('status', SellerDebtStatus::OUTSTANDING)
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
                    'status' => $isCleared ? SellerDebtStatus::RECOVERED : SellerDebtStatus::OUTSTANDING,
                    'recovered_at' => $isCleared ? now() : null,
                ]);

                PaymentTransaction::create([
                    'order_id' => $debt->related_order_id,
                    'type' => PaymentTransactionType::DEBT_RECOVERED,
                    'amount' => $take,
                    'status' => PaymentTransactionStatus::COMPLETED,
                    'party' => PaymentTransactionParty::SELLER,
                    'notes' => "Deducted from the seller's payout on order #{$payoutOrder->order_number}.",
                ]);

                $available = round($available - $take, 2);
                $deducted = round($deducted + $take, 2);
            }

            return $deducted;
        });
    }
}
