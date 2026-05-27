<?php

namespace App\Enums;

enum ProductSaleType: string
{
    case DIRECT_SELLER = 'direct_seller';
    case AUCTION = 'auction';

    public function label(): string
    {
        return match ($this) {
            self::DIRECT_SELLER => 'Direct Seller',
            self::AUCTION => 'Auction',
        };
    }
}
