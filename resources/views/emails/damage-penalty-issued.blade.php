<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f9fafb; padding: 20px; color: #374151;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 28px; border-radius: 12px;">
        <h2 style="color: #dc2626;">Damage Penalty Issued</h2>
        <p>Hi {{ $penalty->seller?->name }},</p>
        <p>
            An admin physically inspected the item from order
            <strong>#{{ $penalty->order?->order_number }}</strong> and confirmed it was damaged or not as
            described. Your seller and auction access has been suspended while this is resolved.
        </p>
        <p>
            Penalty amount: <strong>Rs. {{ number_format((float) $penalty->amount, 2) }}</strong><br>
            Due by: <strong>{{ $penalty->due_at?->format('M d, Y \a\t h:i A') }}</strong>
        </p>
        <p>
            Pay this within {{ (int) config('services.esewa.damage_penalty_days', 7) }} days to keep your account
            in good standing. If it isn't paid by the deadline, your access will be permanently revoked and legal
            action may be taken.
        </p>
        <p style="margin: 28px 0;">
            <a href="{{ route('payment.esewa.penalty-initiate', $penalty) }}"
               style="background: #dc2626; color: #ffffff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;">
                Pay Penalty Now
            </a>
        </p>
        <p style="font-size: 12px; color: #9ca3af;">
            Once the payment is confirmed, an admin will review and restore your access.
        </p>
    </div>
</body>
</html>
