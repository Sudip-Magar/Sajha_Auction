<?php

namespace App\Enums;

enum AuctionStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    // Never written anywhere in this codebase; only ever checked for by
    // isSettled()/isEnded(). Kept as a documented legacy/unused case rather
    // than dropped, in case old data or an external process still uses it.
    case ENDED = 'ended';
    case COMPLETED = 'completed';
    case ENDED_UNSOLD = 'ended_unsold';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::ENDED => 'Ended',
            self::COMPLETED => 'Completed',
            self::ENDED_UNSOLD => 'Ended (Unsold)',
        };
    }
}
