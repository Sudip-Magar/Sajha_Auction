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

    /**
     * Seller-initiated, not admin-initiated: the seller took a live,
     * previously-approved direct-sell listing down themselves. Distinct from
     * REJECTED (admin-initiated, not editable) and CORRECTION (admin asked
     * for changes) - editing an unlisted product and resubmitting sends it
     * back through a normal admin review, same as CORRECTION.
     */
    case UNLISTED = 'unlisted';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CORRECTION => 'Correction',
            self::UNLISTED => 'Unlisted',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->title()->value();
    }
}
