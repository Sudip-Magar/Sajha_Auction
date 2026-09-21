<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Everything that happens to the money on an order when it is cancelled,
 * shared by the order detail page, the admin complaint review and the tests.
 *
 * Two kinds of buyer cancellation exist:
 *  - Group 1 (changed mind / other): the minimum deposit is forfeited and
 *    split between the platform and the seller; anything extra the buyer paid
 *    online is refunded.
 *  - Group 2 (damaged / not as described / documents missing): a complaint
 *    against the seller that the admin reviews. If upheld the buyer is refunded
 *    in full and the seller owes a penalty; if rejected Group 1 rules apply.
 */
class OrderCancellationService
{
    /**
     * @var array<string, string>
     */
    public const GROUP_ONE_REASONS = [
        'changed_mind' => 'I no longer want to buy this item',
        'other' => 'Other reason',
    ];

    /**
     * @var array<string, string>
     */
    public const GROUP_TWO_REASONS = [
        'item_damaged' => 'The item is damaged',
        'not_as_described' => 'The item is not as described / not good quality',
        'documents_missing' => 'Documents are missing',
    ];

    /**
     * @var array<string, string>
     */
    public const BUYER_REASONS = self::GROUP_TWO_REASONS + self::GROUP_ONE_REASONS;

    public static function isComplaintReason(?string $reasonCategory): bool
    {
        return $reasonCategory !== null && array_key_exists($reasonCategory, self::GROUP_TWO_REASONS);
    }

    public static function cancel(Order $order, User $actor, ?string $reasonCategory, ?string $note): void
    {
        $isSeller = (int) $order->seller_id === (int) $actor->id;
        $isComplaint = ! $isSeller && self::isComplaintReason($reasonCategory);

        DB::transaction(function () use ($order, $isSeller, $isComplaint, $reasonCategory, $note): void {
            $order->update([
                'status' => 'cancelled',
                'cancellation_reason_category' => $isSeller ? null : $reasonCategory,
                'cancellation_note' => $note,
                'complaint_status' => $isComplaint ? 'under_review' : null,
            ]);

            if ($isComplaint) {
                return;
            }

            if ($isSeller) {
                self::refundEverything($order, 'The seller cancelled the order.');
            } else {
                self::forfeitMinimumDeposit($order);
            }
        });

        $order->refresh();

        foreach ($order->items as $item) {
            $item->product?->logTimeline(
                'cancelled',
                "Order Cancelled (#{$order->order_number})",
                self::describe($order, $isSeller, $reasonCategory, $note),
                $actor
            );
        }

        // Auction wins mark the product 'sold' the moment the auction ends, so a
        // cancelled auction order has to put the product back on sale.
        if ($order->auction_id) {
            self::relistProduct($order, $actor);
        }
    }

    /**
     * Admin decision on a Group 2 complaint. Upheld: the buyer is refunded in
     * full, the admin advances the seller's penalty to the buyer as
     * compensation, and the seller owes that back as debt. Rejected: the
     * ordinary buyer-cancelled forfeiture applies.
     */
    public static function resolveComplaint(Order $order, Admin $admin, bool $upheld, ?string $resolutionNote): void
    {
        if ($order->complaint_status !== 'under_review') {
            return;
        }

        DB::transaction(function () use ($order, $upheld, $resolutionNote): void {
            $order->update([
                'complaint_status' => $upheld ? 'upheld' : 'rejected',
                'complaint_resolution_note' => $resolutionNote,
            ]);

            if (! $upheld) {
                self::forfeitMinimumDeposit($order);

                return;
            }

            $refund = $order->paidOnline();
            $penalty = SellerDebtService::penaltyFor($order);

            if ($refund > 0) {
                PaymentTransaction::create([
                    'order_id' => $order->id,
                    'type' => PaymentTransaction::TYPE_REFUND_ISSUED,
                    'amount' => $refund,
                    'payment_method' => 'esewa',
                    'status' => 'completed',
                    'party' => 'buyer',
                    'notes' => 'Complaint upheld: everything the buyer paid online is refunded.',
                ]);
            }

            SellerDebtService::record($order, 'Buyer complaint upheld on order #'.$order->order_number.': '.(self::BUYER_REASONS[$order->cancellation_reason_category] ?? 'defect').'.');

            $payout = round($refund + $penalty, 2);

            if ($payout > 0) {
                PayoutRequest::create([
                    'order_id' => $order->id,
                    'recipient_id' => $order->buyer_id,
                    'recipient_role' => 'buyer',
                    'purpose' => PayoutRequest::PURPOSE_BUYER_REFUND,
                    'amount' => $payout,
                    'payout_status' => PayoutRequest::STATUS_AWAITING_DETAILS,
                ]);
            }

            $order->update(['deposit_status' => 'refund_owed']);
        });

        $order->refresh();

        foreach ($order->items as $item) {
            $item->product?->logTimeline(
                'complaint_resolved',
                'Complaint '.($upheld ? 'Upheld' : 'Rejected')." (#{$order->order_number})",
                $upheld
                    ? 'The buyer was refunded in full and the seller owes a penalty of Rs. '.number_format(SellerDebtService::penaltyFor($order), 2).'.'
                    : 'The complaint was rejected; the minimum deposit is forfeited.'
            );
        }
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
            'type' => PaymentTransaction::TYPE_REFUND_ISSUED,
            'amount' => $refund,
            'payment_method' => 'esewa',
            'status' => 'completed',
            'party' => 'buyer',
            'notes' => $reason,
        ]);

        PayoutRequest::create([
            'order_id' => $order->id,
            'recipient_id' => $order->buyer_id,
            'recipient_role' => 'buyer',
            'purpose' => PayoutRequest::PURPOSE_BUYER_REFUND,
            'amount' => $refund,
            'payout_status' => PayoutRequest::STATUS_AWAITING_DETAILS,
        ]);

        $order->update(['deposit_status' => 'refund_owed']);
    }

    /**
     * Group 1 (and a rejected complaint): only the minimum deposit is
     * forfeited, split between the platform and the seller. Any extra the
     * buyer paid online is refunded.
     */
    private static function forfeitMinimumDeposit(Order $order): void
    {
        $paid = $order->paidOnline();

        if ($paid <= 0) {
            return;
        }

        $forfeited = min($paid, $order->minimumDeposit());
        $excess = round($paid - $forfeited, 2);
        $sellerShare = round($forfeited * (float) config('services.esewa.forfeit_seller_share_percentage', 20) / 100, 2);
        $adminShare = round($forfeited - $sellerShare, 2);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'type' => PaymentTransaction::TYPE_DEPOSIT_FORFEITED,
            'amount' => $adminShare,
            'payment_method' => 'esewa',
            'status' => 'completed',
            'party' => 'admin',
            'notes' => "Platform's share of the forfeited deposit.",
        ]);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'type' => PaymentTransaction::TYPE_DEPOSIT_FORFEITED,
            'amount' => $sellerShare,
            'payment_method' => 'esewa',
            'status' => 'completed',
            'party' => 'seller',
            'notes' => "Seller's share of the forfeited deposit.",
        ]);

        if ($sellerShare > 0) {
            self::createSellerPayout($order, $sellerShare);
        }

        if ($excess > 0) {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'type' => PaymentTransaction::TYPE_REFUND_ISSUED,
                'amount' => $excess,
                'payment_method' => 'esewa',
                'status' => 'completed',
                'party' => 'buyer',
                'notes' => 'Amount paid above the minimum deposit is refunded.',
            ]);

            PayoutRequest::create([
                'order_id' => $order->id,
                'recipient_id' => $order->buyer_id,
                'recipient_role' => 'buyer',
                'purpose' => PayoutRequest::PURPOSE_BUYER_REFUND,
                'amount' => $excess,
                'payout_status' => PayoutRequest::STATUS_AWAITING_DETAILS,
            ]);
        }

        $order->update(['deposit_status' => 'forfeited']);
    }

    /**
     * The seller's share is first netted against any debt they owe; whatever
     * is left becomes the payout. Fully offset shares need no transfer.
     */
    private static function createSellerPayout(Order $order, float $sellerShare): void
    {
        $deducted = SellerDebtService::recoverFromPayout($order->seller, $sellerShare, $order);
        $net = round($sellerShare - $deducted, 2);

        PayoutRequest::create([
            'order_id' => $order->id,
            'recipient_id' => $order->seller_id,
            'recipient_role' => 'seller',
            'purpose' => PayoutRequest::PURPOSE_SELLER_FORFEIT_SHARE,
            'amount' => $net,
            'debt_deducted' => $deducted,
            'payout_status' => $net > 0 ? PayoutRequest::STATUS_AWAITING_DETAILS : PayoutRequest::STATUS_SETTLED,
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

    private static function describe(Order $order, bool $isSeller, ?string $reasonCategory, ?string $note): string
    {
        $reasonLabel = $isSeller
            ? 'Cancelled by the seller.'
            : 'Cancelled by the buyer: '.(self::BUYER_REASONS[$reasonCategory] ?? 'No reason given').'.';

        $moneyNote = match (true) {
            $order->complaint_status === 'under_review' => ' The complaint is awaiting admin review; no money has moved yet.',
            $order->deposit_status === 'refund_owed' => ' Everything paid online is owed back to the buyer.',
            $order->deposit_status === 'forfeited' => ' The minimum deposit of Rs. '.number_format($order->minimumDeposit(), 2).' is forfeited (split between the platform and the seller).',
            default => '',
        };

        return $reasonLabel.$moneyNote.($note ? ' Note: '.$note : '');
    }
}
