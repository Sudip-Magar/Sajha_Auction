<?php

namespace App\Enums;

enum ProductAuctionType: string
{
    case TRADITIONAL = 'traditional';

    public function label(): string
    {
        return match ($this) {
            self::TRADITIONAL => 'Traditional Auction',
        };
    }
}
