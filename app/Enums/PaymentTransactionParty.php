<?php

namespace App\Enums;

/** Who a payment_transactions row's money goes to or comes from. */
enum PaymentTransactionParty: string
{
    case ADMIN = 'admin';
    case BUYER = 'buyer';
    case SELLER = 'seller';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::BUYER => 'Buyer',
            self::SELLER => 'Seller',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->title()->value();
    }
}
