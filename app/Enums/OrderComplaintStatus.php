<?php

namespace App\Enums;

/**
 * A Group 2 buyer complaint (damaged / not as described / documents missing)
 * moves through this once an admin physically inspects the item. Null on
 * the order means no complaint was ever filed.
 */
enum OrderComplaintStatus: string
{
    case UNDER_REVIEW = 'under_review';
    case CONFIRMED_DAMAGED = 'confirmed_damaged';
    case NOT_DAMAGED = 'not_damaged';

    public function label(): string
    {
        return match ($this) {
            self::UNDER_REVIEW => 'Under Review',
            self::CONFIRMED_DAMAGED => 'Confirmed Damaged',
            self::NOT_DAMAGED => 'Not Damaged',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace('_', ' ')->title()->value();
    }
}
