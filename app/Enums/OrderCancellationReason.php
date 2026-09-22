<?php

namespace App\Enums;

/**
 * Why a buyer cancelled an order. Group 1 (CHANGED_MIND/OTHER) forfeits the
 * minimum deposit outright; Group 2 is a complaint against the seller that
 * an admin reviews before any money moves - see isComplaintReason().
 */
enum OrderCancellationReason: string
{
    // Group 1
    case CHANGED_MIND = 'changed_mind';
    case OTHER = 'other';

    // Group 2 (complaint)
    case ITEM_DAMAGED = 'item_damaged';
    case NOT_AS_DESCRIBED = 'not_as_described';
    case DOCUMENTS_MISSING = 'documents_missing';

    public function label(): string
    {
        return match ($this) {
            self::CHANGED_MIND => 'I no longer want to buy this item',
            self::OTHER => 'Other reason',
            self::ITEM_DAMAGED => 'The item is damaged',
            self::NOT_AS_DESCRIBED => 'The item is not as described / not good quality',
            self::DOCUMENTS_MISSING => 'Documents are missing',
        };
    }

    public function isComplaintReason(): bool
    {
        return in_array($this, [self::ITEM_DAMAGED, self::NOT_AS_DESCRIBED, self::DOCUMENTS_MISSING], true);
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace('_', ' ')->title()->value();
    }
}
