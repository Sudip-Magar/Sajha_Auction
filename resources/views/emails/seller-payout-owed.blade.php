<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f9fafb; padding: 20px; color: #374151;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 28px; border-radius: 12px;">
        <h2 style="color: #0c8fe8;">A payout is ready for you, {{ $payout->recipient->name }}!</h2>
        <p>{{ $payout->purpose_label }} on order <strong>#{{ $payout->order->order_number }}</strong>: <strong>Rs. {{ number_format($payout->amount, 2) }}</strong>.</p>
        <p>To receive it, log in to Sajha Auction, open the order, and submit your eSewa name, ID/phone, and (optionally) your eSewa QR code. Admin will send the transfer once your details are in.</p>
    </div>
</body>
</html>
