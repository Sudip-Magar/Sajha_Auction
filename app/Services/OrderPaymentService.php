<?php

namespace App\Services;

use App\Enums\OrderPaymentStatus;
use App\Enums\PaymentTransactionMethod;
use App\Enums\PaymentTransactionParty;
use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use App\Enums\PayoutPurpose;
use App\Enums\PayoutRecipientRole;
use App\Enums\PayoutStatus;
use App\Mail\OrderCompletedAdminMail;
use App\Mail\SellerPayoutOwedMail;
use App\Models\Admin;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Notifications\OrderCompletedAdminNotification;
use App\Notifications\SellerPayoutOwedNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        $payout = null;

        DB::transaction(function () use ($order, &$payout): void {
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

            // Auction orders only: the deposit admin already holds online has
            // no destination today - now that the sale is confirmed
            // complete, forward it to the seller in full (no platform fee).
            // Direct-sell orders never route money through the platform this
            // way (cash-on-handover only), so they're untouched.
            if ($order->auction_id) {
                $payout = self::createSaleProceedsPayout($order);
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

        if (! $order->auction_id) {
            return;
        }

        if ($payout && $payout->payout_status === PayoutStatus::AWAITING_DETAILS && $order->seller) {
            self::notifySafely($order->seller, new SellerPayoutOwedNotification($payout));
            Mail::to($order->seller->email)->send(new SellerPayoutOwedMail($payout));
        }

        Admin::all()->each(function (Admin $admin) use ($order): void {
            self::notifySafely($admin, new OrderCompletedAdminNotification($order));
            Mail::to($admin->email)->send(new OrderCompletedAdminMail($order));
        });
    }

    /**
     * The seller's proceeds are first netted against any debt they owe;
     * whatever is left becomes the payout. Mirrors
     * OrderCancellationService::createSellerPayout() for the forfeit-share
     * case.
     */
    private static function createSaleProceedsPayout(Order $order): ?PayoutRequest
    {
        $paidOnline = $order->paidOnline();

        if ($paidOnline <= 0) {
            return null;
        }

        $deducted = SellerDebtService::recoverFromPayout($order->seller, $paidOnline, $order);
        $net = round($paidOnline - $deducted, 2);

        return PayoutRequest::create([
            'order_id' => $order->id,
            'recipient_id' => $order->seller_id,
            'recipient_role' => PayoutRecipientRole::SELLER,
            'purpose' => PayoutPurpose::SELLER_SALE_PROCEEDS,
            'amount' => $net,
            'debt_deducted' => $deducted,
            'payout_status' => $net > 0 ? PayoutStatus::AWAITING_DETAILS : PayoutStatus::SETTLED,
        ]);
    }

    /**
     * Mirrors OrderCancellationService::notifySafely(): these notifications
     * broadcast in-process (ShouldBroadcastNow), so a transient Reverb
     * hiccup must not turn an already-committed completion into a fatal
     * error for whoever triggered it.
     */
    private static function notifySafely(object $notifiable, object $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (BroadcastException $exception) {
            Log::warning('Order-completion notification broadcast failed.', [
                'notifiable' => $notifiable::class,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);
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
