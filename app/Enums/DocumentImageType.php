<?php

namespace App\Enums;

enum DocumentImageType: string
{
    case CITIZENSHIPFRONT = 'citizenship Front';
    case CITIZENSHIPBACK = 'citizenship Back';
    case PASSPORT = 'passport';
    case DRIVING_LICENSE = 'driving_license';
    case NATIONAL_ID = 'national_id';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CITIZENSHIPFRONT => 'Citizenship front',
            self::CITIZENSHIPBACK => 'Citizenship back',
            self::PASSPORT => 'Passport',
            self::DRIVING_LICENSE => 'Driving License',
            self::NATIONAL_ID => 'National ID',
            self::OTHER => 'Other Document',
        };
    }
}
