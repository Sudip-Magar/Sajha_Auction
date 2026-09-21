<?php

namespace App\Enums;

enum StatusState: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom(strtolower((string) $value))?->label() ?? str((string) $value)->title()->value();
    }
}
