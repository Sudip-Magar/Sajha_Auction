<?php

namespace App\Enums;

enum ProductCondition: string
{
    case NEW = 'new';
    case LIKE_NEW = 'like-new';
    case LIGHTLY_USED = 'lightly-used';
    case WELL_USED = 'well-used';
    case REFURBISHED = 'refurbished';
    case USED = 'used';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Brand New',
            self::LIKE_NEW => 'Like New',
            self::LIGHTLY_USED => 'Lightly Used',
            self::WELL_USED => 'Well Used',
            self::REFURBISHED => 'Refurbished',
            self::USED => 'Used',
        };
    }

    /**
     * Longer wording used on the seller's listing form.
     */
    public function formLabel(): string
    {
        return match ($this) {
            self::NEW => 'Brand New',
            self::LIKE_NEW => 'Like New (Minimal Use)',
            self::LIGHTLY_USED => 'Lightly Used',
            self::WELL_USED => 'Well Used / Fair',
            self::REFURBISHED => 'Refurbished',
            self::USED => 'Used',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace(['-', '_'], ' ')->title()->value();
    }
}
