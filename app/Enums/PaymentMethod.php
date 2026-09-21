<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH_ON_MEETUP = 'cash_on_meetup';
    case CASH_ON_DELIVERY = 'cash_on_delivery';
    case KHALTI = 'khalti';
    case ESEWA = 'esewa';
    case WALLET = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::CASH_ON_MEETUP => 'Cash on Meetup / Handover',
            self::CASH_ON_DELIVERY => 'Cash on Delivery',
            self::KHALTI => 'Khalti',
            self::ESEWA => 'eSewa',
            self::WALLET => 'Wallet',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace('_', ' ')->title()->value();
    }
}
