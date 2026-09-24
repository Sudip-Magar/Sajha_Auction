<?php

namespace App\Services;

use App\Enums\OrderCancellationReason;
use App\Enums\OrderComplaintStatus;
use App\Enums\OrderDepositStatus;
use App\Enums\PaymentTransactionMethod;
use App\Enums\PaymentTransactionParty;
use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use App\Enums\PayoutPurpose;
use App\Enums\PayoutRecipientRole;
use App\Enums\PayoutStatus;
use App\Mail\OrderCancelledAdminMail;
use App\Mail\SellerPayoutOwedMail;
use App\Models\Admin;
use App\Models\DamagePenalty;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Notifications\ComplaintFiledNotification;
use App\Notifications\OrderCancelledAdminNotification;
use App\Notifications\SellerPayoutOwedNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Everything that happens to the money on an order when it is cancelled,
 * shared by the order detail page, the admin complaint review and the tests.
 *
 * Two kinds of buyer cancellation exist:
 *  - Group 1 (changed mind / other): the minimum deposit is forfeited and
 *    split between the platform and the seller; anything extra the buyer paid
 *    online is refunded.
 *  - Group 2 (damaged / not as described / documents missing): a complaint
 *    against the seller. The order waits as "under review" until an admin
 *    physically inspects the item and records a verdict via
 *    recordDamageVerdict() - confirmed damaged refunds the buyer in full and
 *    sends the seller a separate 30% penalty (DamagePenaltyService); not
 *    damaged falls back to the Group 1 forfeiture rule.
 */
class OrderCancellationService
{
    public static function cancel(Order $order, User $actor, ?OrderCancellationReason $reason, ?string $note, bool $escalateToComplaint = false): void
    {
        $isSeller = (int) $order->seller_id === (int) $actor->id;
        // $escalateToComplaint lets a buyer route an "Other reason"
        // cancellation into the same admin-reviewed complaint path as the
        // dedicated damage/not-as-described/documents-missing reasons,
        // instead of the outright Group 1 forfeiture - see the "File a
        // Complaint" button on the order detail page.
        $isComplaint = ! $isSeller && ($reason?->isComplaintReason() === true || $escalateToComplaint);

        $sellerPayout = null;

        DB::transaction(function () use ($order, $isSeller, $isComplaint, $reason, $note, &$sellerPayout): void {
            $order->update([
                'status' => 'cancelled',
                'cancellation_reason_category' => $isSeller ? null : $reason,
                'cancellation_note' => $note,
                'complaint_status' => $isComplaint ? OrderComplaintStatus::UNDER_REVIEW : null,
            ]);

            if ($isComplaint) {
                return;
            }

            if ($isSeller) {
                self::refundEverything($order, 'The seller cancelled the order.');
            } else {
                $sellerPayout = self::forfeitMinimumDeposit($order);
            }
        });

        // refresh() reloads the items relation but not its nested product
        // eager load, so it has to be re-specified explicitly here - without
        // it, every $item->product access below becomes its own query.
        $order->refresh()->load('items.product');

        foreach ($order->items as $item) {
            $item->product?->logTimeline(
                'cancelled',
                "Order Cancelled (#{$order->order_number})",
                self::describe($order, $isSeller, $reason, $note),
                $actor
            );
        }

        if ($isComplaint) {
            self::notifyAdminsOfComplaint($order);
        }

        // Auction wins mark the product 'sold' the moment the auction ends, so a
        // cancelled auction order has to put the product back on sale.
        if ($order->auction_id) {
            self::relistProduct($order, $actor);
            self::notifyAdminsOfCancellation($order);
        }

        self::notifySellerOfPayout($order, $sellerPayout);
    }

    /**
     * Admin's physical-inspection verdict on a Group 2 complaint.
     *
     * Confirmed damaged: the buyer is refunded everything paid online (from
     * the deposit the admin already holds - unchanged), the seller's access
     * is revoked immediately, and a separate 30% penalty is issued for the
     * seller to pay directly (DamagePenaltyService; not a payout deduction).
     *
     * Not damaged: the complaint is rejected and the ordinary Group 1
     * forfeiture applies - the buyer keeps the product.
     */
    public static function recordDamageVerdict(Order $order, Admin $admin, bool $confirmedDamaged, ?string $resolutionNote): ?DamagePenalty
    {
        if ($order->complaint_status !== OrderComplaintStatus::UNDER_REVIEW) {
            return null;
        }

        $resolved = false;
        $sellerPayout = null;

        $penalty = DB::transaction(function () use ($order, $confirmedDamaged, $resolutionNote, &$resolved, &$sellerPayout): ?DamagePenalty {
            // Re-checked under a row lock so a double-click or two concurrent
            // admin sessions can't both pass the guard and duplicate the
            // refund and the penalty.
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $order || $order->complaint_status !== OrderComplaintStatus::UNDER_REVIEW) {
                return null;
            }

            $resolved = true;

            $order->update([
                'complaint_status' => $confirmedDamaged ? OrderComplaintStatus::CONFIRMED_DAMAGED : OrderComplaintStatus::NOT_DAMAGED,
                'complaint_resolution_note' => $resolutionNote,
            ]);

            if (! $confirmedDamaged) {
                $sellerPayout = self::forfeitMinimumDeposit($order);

                return null;
            }

            $refund = $order->paidOnline();

            if ($refund > 0) {
                PaymentTransaction::create([
                    'order_id' => $order->id,
                    'type' => PaymentTransactionType::REFUND_ISSUED,
                    'amount' => $refund,
                    'payment_method' => PaymentTransactionMethod::ESEWA,
                    'status' => PaymentTransactionStatus::COMPLETED,
                    'party' => PaymentTransactionParty::BUYER,
                    'notes' => 'Confirmed damaged: everything the buyer paid online is refunded.',
                ]);

                PayoutRequest::create([
                    'order_id' => $order->id,
                    'recipient_id' => $order->buyer_id,
                    'recipient_role' => PayoutRecipientRole::BUYER,
                    'purpose' => PayoutPurpose::BUYER_REFUND,
                    'amount' => $refund,
                    'payout_status' => PayoutStatus::AWAITING_DETAILS,
                ]);
            }

            $order->update(['deposit_status' => OrderDepositStatus::REFUND_OWED]);

            return DamagePenaltyService::issue($order);
        });

        if (! $resolved) {
            return null;
        }

        // Same nested-eager-load loss as cancel() above.
        $order->refresh()->load('items.product');

        foreach ($order->items as $item) {
            $item->product?->logTimeline(
                'complaint_resolved',
                'Damage Verdict: '.($confirmedDamaged ? 'Confirmed Damaged' : 'Not Damaged')." (#{$order->order_number})",
                $confirmedDamaged
                    ? 'The buyer was refunded in full. The seller\'s access has been revoked and a penalty of Rs. '.number_format($penalty->amount, 2).' has been issued.'
                    : 'The complaint was rejected; the minimum deposit is forfeited.'
            );
        }

        if ($penalty) {
            DamagePenaltyService::notifySeller($penalty);
        }

        self::notifySellerOfPayout($order, $sellerPayout);

        return $penalty;
    }

    /**
     * Seller-caused cancellation: everything paid online goes back, no penalty.
     */
    private static function refundEverything(Order $order, string $reason): void
    {
        $refund = $order->paidOnline();

        if ($refund <= 0) {
            return;
        }

        PaymentTransaction::create([
            'order_id' => $order->id,
            'type' => PaymentTransactionType::REFUND_ISSUED,
            'amount' => $refund,
            'payment_method' => PaymentTransactionMethod::ESEWA,
            'status' => PaymentTransactionStatus::COMPLETED,
            'party' => PaymentTransactionParty::BUYER,
            'notes' => $reason,
        ]);

        PayoutRequest::create([
            'order_id' => $order->id,
            'recipient_id' => $order->buyer_id,
            'recipient_role' => PayoutRecipientRole::BUYER,
            'purpose' => PayoutPurpose::BUYER_REFUND,
            'amount' => $refund,
            'payout_status' => PayoutStatus::AWAITING_DETAILS,
        ]);

        $order->update(['deposit_status' => OrderDepositStatus::REFUND_OWED]);
    }

    /**
     * Group 1 (and a rejected complaint): only the minimum deposit is
     * forfeited, split between the platform and the seller. Any extra the
     * buyer paid online is refunded.
     */
    private static function forfeitMinimumDeposit(Order $order): ?PayoutRequest
    {
        $paid = $order->paidOnline();

        if ($paid <= 0) {
            return null;
        }

        $forfeited = min($paid, $order->minimumDeposit());
        $excess = round($paid - $forfeited, 2);
        $sellerShare = round($forfeited * (float) config('services.esewa.forfeit_seller_share_percentage', 20) / 100, 2);
        $adminShare = round($forfeited - $sellerShare, 2);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'type' => PaymentTransactionType::DEPOSIT_FORFEITED,
            'amount' => $adminShare,
            'payment_method' => PaymentTransactionMethod::ESEWA,
            'status' => PaymentTransactionStatus::COMPLETED,
            'party' => PaymentTransactionParty::ADMIN,
            'notes' => "Platform's share of the forfeited deposit.",
        ]);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'type' => PaymentTransactionType::DEPOSIT_FORFEITED,
            'amount' => $sellerShare,
            'payment_method' => PaymentTransactionMethod::ESEWA,
            'status' => PaymentTransactionStatus::COMPLETED,
            'party' => PaymentTransactionParty::SELLER,
            'notes' => "Seller's share of the forfeited deposit.",
        ]);

        $sellerPayout = $sellerShare > 0 ? self::createSellerPayout($order, $sellerShare) : null;

        if ($excess > 0) {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'type' => PaymentTransactionType::REFUND_ISSUED,
                'amount' => $excess,
                'payment_method' => PaymentTransactionMethod::ESEWA,
                'status' => PaymentTransactionStatus::COMPLETED,
                'party' => PaymentTransactionParty::BUYER,
                'notes' => 'Amount paid above the minimum deposit is refunded.',
            ]);

            PayoutRequest::create([
                'order_id' => $order->id,
                'recipient_id' => $order->buyer_id,
                'recipient_role' => PayoutRecipientRole::BUYER,
                'purpose' => PayoutPurpose::BUYER_REFUND,
                'amount' => $excess,
                'payout_status' => PayoutStatus::AWAITING_DETAILS,
            ]);
        }

        $order->update(['deposit_status' => OrderDepositStatus::FORFEITED]);

        return $sellerPayout;
    }

    /**
     * The seller's share is first netted against any debt they owe; whatever
     * is left becomes the payout. Fully offset shares need no transfer.
     */
    private static function createSellerPayout(Order $order, float $sellerShare): PayoutRequest
    {
        $deducted = SellerDebtService::recoverFromPayout($order->seller, $sellerShare, $order);
        $net = round($sellerShare - $deducted, 2);

        return PayoutRequest::create([
            'order_id' => $order->id,
            'recipient_id' => $order->seller_id,
            'recipient_role' => PayoutRecipientRole::SELLER,
            'purpose' => PayoutPurpose::SELLER_FORFEIT_SHARE,
            'amount' => $net,
            'debt_deducted' => $deducted,
            'payout_status' => $net > 0 ? PayoutStatus::AWAITING_DETAILS : PayoutStatus::SETTLED,
        ]);
    }

    private static function relistProduct(Order $order, User $actor): void
    {
        foreach ($order->items as $item) {
            if ($item->product && $item->product->status === 'sold') {
                $item->product->update(['status' => 'active']);
                $item->product->logTimeline(
                    'relisted',
                    "Product Relisted (#{$order->order_number})",
                    'The auction sale fell through, so this listing is available again. The concluded auction itself is kept as-is for history; the seller can start a new one whenever ready.',
                    $actor
                );
            }
        }
    }

    private static function describe(Order $order, bool $isSeller, ?OrderCancellationReason $reason, ?string $note): string
    {
        $reasonLabel = $isSeller
            ? 'Cancelled by the seller.'
            : 'Cancelled by the buyer: '.($reason?->label() ?? 'No reason given').'.';

        $moneyNote = match (true) {
            $order->complaint_status === OrderComplaintStatus::UNDER_REVIEW => ' The complaint is awaiting admin review; no money has moved yet.',
            $order->deposit_status === OrderDepositStatus::REFUND_OWED => ' Everything paid online is owed back to the buyer.',
            $order->deposit_status === OrderDepositStatus::FORFEITED => ' The minimum deposit of Rs. '.number_format($order->minimumDeposit(), 2).' is forfeited (split between the platform and the seller).',
            default => '',
        };

        return $reasonLabel.$moneyNote.($note ? ' Note: '.$note : '');
    }

    private static function notifyAdminsOfComplaint(Order $order): void
    {
        Admin::all()->each(function (Admin $admin) use ($order): void {
            self::notifySafely($admin, new ComplaintFiledNotification($order));
        });
    }

    /**
     * Auction orders only, per the seller notification/payout work this
     * accompanies - direct-sell cancellations keep working exactly as
     * before, with no payout-owed prompt.
     */
    private static function notifyAdminsOfCancellation(Order $order): void
    {
        Admin::all()->each(function (Admin $admin) use ($order): void {
            self::notifySafely($admin, new OrderCancelledAdminNotification($order));
            Mail::to($admin->email)->send(new OrderCancelledAdminMail($order));
        });
    }

    /**
     * Auction orders only - see notifyAdminsOfCancellation(). Skipped when
     * there's nothing to actually receive (no payout was created, or its
     * share was fully absorbed by outstanding debt).
     */
    private static function notifySellerOfPayout(Order $order, ?PayoutRequest $payout): void
    {
        if (! $order->auction_id || ! $payout || $payout->payout_status !== PayoutStatus::AWAITING_DETAILS || ! $order->seller) {
            return;
        }

        self::notifySafely($order->seller, new SellerPayoutOwedNotification($payout));
        Mail::to($order->seller->email)->send(new SellerPayoutOwedMail($payout));
    }

    /**
     * Mirrors DamagePenaltyService::notifySafely(): these notifications
     * broadcast in-process (ShouldBroadcastNow), so a transient Reverb
     * hiccup must not turn an already-committed cancellation into a fatal
     * error for whoever triggered it.
     */
    private static function notifySafely(object $notifiable, object $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (BroadcastException $exception) {
            Log::warning('Order-cancellation notification broadcast failed.', [
                'notifiable' => $notifiable::class,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
