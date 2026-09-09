<?php

namespace App\Enums;

enum ProductImageType: string
{
    case GENERAL = 'general';
    case PROOF = 'proof';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General',
            self::PROOF => 'Proof of Product',
        };
    }
}
