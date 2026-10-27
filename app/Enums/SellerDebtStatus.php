<?php

namespace App\Enums;

enum SellerDebtStatus: string
{
    case OUTSTANDING = 'outstanding';
    case RECOVERED = 'recovered';

    public function label(): string
    {
        return match ($this) {
            self::OUTSTANDING => 'Outstanding',
            self::RECOVERED => 'Recovered',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->title()->value();
    }
}
