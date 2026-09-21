<?php

namespace App\Enums;

enum ProductStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SOLD = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::SOLD => 'Sold',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace('_', ' ')->title()->value();
    }
}
