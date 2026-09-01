<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f9fafb; padding: 20px; color: #374151;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 28px; border-radius: 12px;">
        <h2 style="color: #0c8fe8;">Congratulations, {{ $auction->winner?->name }}!</h2>
        <p>You won the auction for <strong>{{ $auction->product?->name }}</strong>.</p>
        <p>Winning price: <strong>Rs. {{ number_format((float) $auction->winning_price, 2) }}</strong></p>
        <p>An order has been created in your Sajha Auction account. Please review it to arrange handover with the seller.</p>
    </div>
</body>
</html>
