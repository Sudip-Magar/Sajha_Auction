<?php

namespace App\Enums;

enum ProductAuctionType: string
{
    case PENNY = 'penny';
    case TRADITIONAL = 'traditional';

    public function label(): string
    {
        return match ($this) {
            self::PENNY => 'Penny Auction',
            self::TRADITIONAL => 'Traditional Auction',
        };
    }
}
