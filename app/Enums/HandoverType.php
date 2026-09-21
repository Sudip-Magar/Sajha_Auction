<?php

namespace App\Enums;

enum HandoverType: string
{
    case MEETUP = 'meetup';
    case DELIVERY = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::MEETUP => 'In-Person Meetup',
            self::DELIVERY => 'Delivery',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace('_', ' ')->title()->value();
    }
}
