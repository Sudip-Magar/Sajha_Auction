<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f9fafb; padding: 20px; color: #374151;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 28px; border-radius: 12px;">
        <h2 style="color: #0c8fe8;">Auction order completed</h2>
        <p>Order <strong>#{{ $order->order_number }}</strong> ({{ $order->items->first()?->product?->name }}) was marked completed by the seller.</p>
        <p>The deposit held online for this order is now owed to the seller as sale proceeds. A payout request has been created; send it once the seller submits their eSewa details.</p>
    </div>
</body>
</html>
