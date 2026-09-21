<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case MEETUP_SCHEDULED = 'meetup_scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Confirmation',
            self::CONFIRMED => 'Order Confirmed',
            self::MEETUP_SCHEDULED => 'Meetup Scheduled',
            self::COMPLETED => 'Completed & Handed Over',
            self::CANCELLED => 'Cancelled',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace('_', ' ')->title()->value();
    }
}
