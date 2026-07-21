<?php

namespace App\Enums;

enum ProductNegotiability: string
{
    case ANY = 'any';
    case NEGOTIABLE = 'negotiable';
    case FIXED = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::ANY => 'Any',
            self::NEGOTIABLE => 'Negotiable',
            self::FIXED => 'Fixed',
        };
    }
}
