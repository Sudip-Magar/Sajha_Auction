<?php

namespace App\Enums;

enum ComplaintMessageSender: string
{
    case ADMIN = 'admin';
    case BUYER = 'buyer';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::BUYER => 'Buyer',
        };
    }
}
