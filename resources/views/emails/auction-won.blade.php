<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f9fafb; padding: 20px; color: #374151;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 28px; border-radius: 12px;">
        <h2 style="color: #0c8fe8;">Congratulations, {{ $auction->winner?->name }}!</h2>
        <p>You won the auction for <strong>{{ $auction->product?->name }}</strong>.</p>
        <p>Winning price: <strong>Rs. {{ number_format((float) $auction->winning_price, 2) }}</strong></p>
        <p>An order has been created in your Sajha Auction account. Please review it to arrange handover with the seller.</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">

        <h3 style="color: #111827; font-size: 15px;">Before you pay and meet up, please read this</h3>
        <ul style="padding-left: 18px; line-height: 1.6;">
            <li>Cancelling because you simply changed your mind forfeits your deposit - you will not get that payment back.</li>
            <li>If the item turns out to be damaged, not as described, or missing its documents, file a complaint instead of an ordinary cancellation and you will be refunded in full once confirmed.</li>
            <li><strong>Once you pay the remaining balance in cash at the meetup, that payment is final and cannot be refunded.</strong> Inspect the item carefully before paying - if something is wrong, complain immediately rather than completing the handover.</li>
        </ul>
    </div>
</body>
</html>
