<?php

namespace App\Services;

use App\Enums\OrderPaymentStatus;
use App\Enums\PaymentTransactionMethod;
use App\Enums\PaymentTransactionParty;
use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use App\Enums\PayoutStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Order completion and the payout hand-off. Payouts are never automated: the
 * admin sends the money outside the app (eSewa) and then marks it sent here.
 */
class OrderPaymentService
{
    /**
     * Seller confirms the handover: the buyer's remaining balance is recorded
     * as cash, stock is decremented and the product timeline updated.
     */
    public static function complete(Order $order, User $actor): void
    {
        DB::transaction(function () use ($order): void {
            $remaining = $order->remainingAmount();

            $order->update(['status' => 'completed', 'payment_status' => OrderPaymentStatus::PAID]);

            if ($remaining > 0) {
                PaymentTransaction::create([
                    'order_id' => $order->id,
                    'type' => PaymentTransactionType::BALANCE_PAID_CASH,
                    'amount' => $remaining,
                    'payment_method' => PaymentTransactionMethod::CASH,
                    'status' => PaymentTransactionStatus::COMPLETED,
                    'party' => PaymentTransactionParty::SELLER,
                    'notes' => 'Cash paid at the meetup.',
                ]);
            }
        });

        foreach ($order->items as $item) {
            if (! $item->product) {
                continue;
            }

            $item->product->decrement('quantity', min($item->quantity, $item->product->quantity));
            if ($item->product->quantity <= 0) {
                $item->product->update(['status' => 'sold']);
            }

            $item->product->logTimeline(
                'completed',
                "Product Handed Over & Sold (#{$order->order_number})",
                "Order successfully completed by buyer {$order->buyer?->name} and seller {$order->seller?->name}.",
                $actor
            );
        }
    }

    /**
     * The payout recipient (buyer or seller) tells us where to send the money.
     */
    public static function submitPayoutDetails(PayoutRequest $payout, User $user, string $esewaName, string $esewaPhone, ?string $qrImagePath): bool
    {
        if ((int) $payout->recipient_id !== (int) $user->id || $payout->payout_status !== PayoutStatus::AWAITING_DETAILS) {
            return false;
        }

        $payout->update([
            'esewa_name' => $esewaName,
            'esewa_phone' => $esewaPhone,
            'qr_image_path' => $qrImagePath,
            'payout_status' => PayoutStatus::PENDING,
            'details_submitted_at' => now(),
        ]);

        return true;
    }

    /**
     * Admin has made the real eSewa transfer; record it.
     */
    public static function markSent(PayoutRequest $payout, Admin $admin): bool
    {
        if ($payout->payout_status !== PayoutStatus::PENDING) {
            return false;
        }

        DB::transaction(function () use ($payout): void {
            $payout->update(['payout_status' => PayoutStatus::SENT, 'sent_at' => now()]);

            PaymentTransaction::create([
                'order_id' => $payout->order_id,
                'type' => PaymentTransactionType::PAYOUT_SENT,
                'amount' => $payout->amount,
                'payment_method' => PaymentTransactionMethod::ESEWA,
                'status' => PaymentTransactionStatus::COMPLETED,
                // recipient_role (buyer|seller) and party (admin|buyer|seller) are
                // different enums for different tables; the raw value crosses
                // that boundary cleanly since both share the same string here.
                'party' => $payout->recipient_role->value,
                'notes' => $payout->purpose_label.' sent to '.$payout->esewa_name.' ('.$payout->esewa_phone.').',
            ]);
        });

        return true;
    }
}
