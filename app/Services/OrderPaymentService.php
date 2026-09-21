<?php

namespace App\Services;

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

            $order->update(['status' => 'completed', 'payment_status' => 'paid']);

            if ($remaining > 0) {
                PaymentTransaction::create([
                    'order_id' => $order->id,
                    'type' => PaymentTransaction::TYPE_BALANCE_PAID_CASH,
                    'amount' => $remaining,
                    'payment_method' => 'cash',
                    'status' => 'completed',
                    'party' => 'seller',
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
        if ((int) $payout->recipient_id !== (int) $user->id || $payout->payout_status !== PayoutRequest::STATUS_AWAITING_DETAILS) {
            return false;
        }

        $payout->update([
            'esewa_name' => $esewaName,
            'esewa_phone' => $esewaPhone,
            'qr_image_path' => $qrImagePath,
            'payout_status' => PayoutRequest::STATUS_PENDING,
            'details_submitted_at' => now(),
        ]);

        return true;
    }

    /**
     * Admin has made the real eSewa transfer; record it.
     */
    public static function markSent(PayoutRequest $payout, Admin $admin): bool
    {
        if ($payout->payout_status !== PayoutRequest::STATUS_PENDING) {
            return false;
        }

        DB::transaction(function () use ($payout): void {
            $payout->update(['payout_status' => PayoutRequest::STATUS_SENT, 'sent_at' => now()]);

            PaymentTransaction::create([
                'order_id' => $payout->order_id,
                'type' => PaymentTransaction::TYPE_PAYOUT_SENT,
                'amount' => $payout->amount,
                'payment_method' => 'esewa',
                'status' => 'completed',
                'party' => $payout->recipient_role,
                'notes' => $payout->purpose_label.' sent to '.$payout->esewa_name.' ('.$payout->esewa_phone.').',
            ]);
        });

        return true;
    }
}
