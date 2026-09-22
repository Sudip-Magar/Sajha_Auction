<?php

namespace App\Enums;

enum PayoutPurpose: string
{
    case SELLER_FORFEIT_SHARE = 'seller_forfeit_share';
    case BUYER_REFUND = 'buyer_refund';

    public function label(): string
    {
        return match ($this) {
            self::SELLER_FORFEIT_SHARE => "Seller's share of the forfeited deposit",
            self::BUYER_REFUND => 'Refund to buyer',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace('_', ' ')->title()->value();
    }
}
