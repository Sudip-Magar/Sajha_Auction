<?php

namespace App\Enums;

enum PayoutRecipientRole: string
{
    case BUYER = 'buyer';
    case SELLER = 'seller';

    public function label(): string
    {
        return match ($this) {
            self::BUYER => 'Buyer',
            self::SELLER => 'Seller',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->title()->value();
    }
}
