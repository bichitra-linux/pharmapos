<?php

declare(strict_types=1);

namespace App\Enums;

enum DosageForm: string
{
    case Tablet = 'tablet';
    case Capsule = 'capsule';
    case Syrup = 'syrup';
    case Injection = 'injection';
    case Ointment = 'ointment';
    case Cream = 'cream';
    case Drops = 'drops';
    case Inhaler = 'inhaler';
    case Powder = 'powder';
    case Gel = 'gel';
    case Lotion = 'lotion';
    case Suspension = 'suspension';
    case Solution = 'solution';
    case Suppository = 'suppository';
    case Patch = 'patch';
    case Spray = 'spray';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Tablet => 'Tablet',
            self::Capsule => 'Capsule',
            self::Syrup => 'Syrup',
            self::Injection => 'Injection',
            self::Ointment => 'Ointment',
            self::Cream => 'Cream',
            self::Drops => 'Drops',
            self::Inhaler => 'Inhaler',
            self::Powder => 'Powder',
            self::Gel => 'Gel',
            self::Lotion => 'Lotion',
            self::Suspension => 'Suspension',
            self::Solution => 'Solution',
            self::Suppository => 'Suppository',
            self::Patch => 'Patch',
            self::Spray => 'Spray',
            self::Other => 'Other',
        };
    }
}
