<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

/**
 * Centralizes what happens to an auction-win deposit when an order is
 * cancelled, since OrderDetail and Orders (the detail page and the list
 * page) both need to make the exact same call here.
 */
class OrderCancellationService
{
    /**
     * @var array<string, string>
     */
    public const BUYER_REASONS = [
        'defect_mismatch' => 'The item had a defect or did not match the listing',
        'changed_mind' => 'I no longer want to buy this item',
        'other' => 'Other reason',
    ];

    /**
     * A seller-caused or genuinely defective item refunds the deposit; a
     * buyer simply changing their mind forfeits it, since the deposit's
     * whole purpose is discouraging exactly that after winning an auction.
     */
    public static function cancel(Order $order, User $actor, ?string $reasonCategory, ?string $note): void
    {
        $isSeller = (int) $order->seller_id === (int) $actor->id;

        $attributes = [
            'status' => 'cancelled',
            'cancellation_reason_category' => $isSeller ? null : $reasonCategory,
            'cancellation_note' => $note,
        ];

        if ($order->deposit_status === 'paid') {
            $attributes['deposit_status'] = ($isSeller || $reasonCategory === 'defect_mismatch')
                ? 'refund_owed'
                : 'forfeited';
        }

        $order->update($attributes);

        $description = self::describe($order, $isSeller, $reasonCategory, $note);

        foreach ($order->items as $item) {
            $item->product?->logTimeline('cancelled', "Order Cancelled (#{$order->order_number})", $description, $actor);
        }

        // Only auction wins mark the product 'sold' before the order actually
        // completes (AuctionEngineService::determineWinner() does this the
        // instant the auction ends). Direct-sell orders only ever reach
        // 'sold' on completion, and a completed order can't be cancelled, so
        // this relisting step is specifically for the auction-win case -
        // otherwise the product would stay permanently unsellable after a
        // buyer backs out.
        if ($order->auction_id) {
            self::relistProduct($order, $actor);
        }
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

        $depositNote = match ($order->deposit_status) {
            'refund_owed' => ' The paid deposit of Rs. '.number_format((float) $order->deposit_amount, 2).' is owed back to the buyer.',
            'forfeited' => ' The paid deposit of Rs. '.number_format((float) $order->deposit_amount, 2).' is forfeited.',
            default => '',
        };

        return $reasonLabel.$depositNote.($note ? ' Note: '.$note : '');
    }
}
