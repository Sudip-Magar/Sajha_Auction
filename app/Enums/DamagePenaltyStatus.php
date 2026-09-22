<?php

namespace App\Enums;

enum DamagePenaltyStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::EXPIRED => 'Expired',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->title()->value();
    }
}
