<?php

namespace App\Enums;

/**
 * The admin moderation state of a listing - not to be confused with
 * {@see ProductStatus}, which is the unrelated sale-lifecycle column
 * (pending/active/sold).
 */
enum ProductApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CORRECTION = 'correction';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CORRECTION => 'Correction',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->title()->value();
    }
}
