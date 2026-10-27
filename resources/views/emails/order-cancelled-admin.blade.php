<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f9fafb; padding: 20px; color: #374151;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 28px; border-radius: 12px;">
        <h2 style="color: #0c8fe8;">Auction order cancelled</h2>
        <p>Order <strong>#{{ $order->order_number }}</strong> ({{ $order->items->first()?->product?->name }}) was cancelled.</p>
        <p>
            Reason: <strong>{{ $order->cancellation_reason_category?->label() ?? 'Not given' }}</strong>.
            @if($order->complaint_status)
                This is a formal complaint awaiting your verdict - review it in the admin complaints queue.
            @else
                The minimum deposit has been forfeited and split between the platform and the seller; any extra the buyer paid online is refunded.
            @endif
        </p>
        @if($order->cancellation_note)
            <p style="color: #6b7280;">Buyer's note: {{ $order->cancellation_note }}</p>
        @endif
    </div>
</body>
</html>
