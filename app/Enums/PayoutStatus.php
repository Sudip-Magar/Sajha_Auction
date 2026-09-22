<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case AWAITING_DETAILS = 'awaiting_details';
    case PENDING = 'pending';
    case SENT = 'sent';

    /** The recipient's share was fully offset by debt, so nothing is transferred. */
    case SETTLED = 'settled';

    public function label(): string
    {
        return match ($this) {
            self::AWAITING_DETAILS => 'Awaiting Details',
            self::PENDING => 'Pending Transfer',
            self::SENT => 'Sent',
            self::SETTLED => 'Settled (Offset by Debt)',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace('_', ' ')->title()->value();
    }
}
