<?php

namespace App\Enums;

/**
 * The method behind one payment_transactions row: esewa | cash. Distinct
 * from {@see PaymentMethod} (orders.payment_method), which has a wider,
 * unrelated vocabulary (cash_on_meetup, khalti, wallet, ...).
 */
enum PaymentTransactionMethod: string
{
    case ESEWA = 'esewa';
    case CASH = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::ESEWA => 'eSewa',
            self::CASH => 'Cash',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->title()->value();
    }
}
