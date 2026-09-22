<?php

namespace App\Enums;

enum OrderDepositStatus: string
{
    case NOT_REQUIRED = 'not_required';
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUND_OWED = 'refund_owed';
    case FORFEITED = 'forfeited';

    public function label(): string
    {
        return match ($this) {
            self::NOT_REQUIRED => 'Not Required',
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
            self::REFUND_OWED => 'Refund Owed',
            self::FORFEITED => 'Forfeited',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace('_', ' ')->title()->value();
    }
}
