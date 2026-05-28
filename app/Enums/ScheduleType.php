<?php

declare(strict_types=1);

namespace App\Enums;

enum ScheduleType: string
{
    case H = 'h';
    case H1 = 'h1';
    case X = 'x';
    case G = 'g';
    case OTC = 'otc';

    public function label(): string
    {
        return match ($this) {
            self::H => 'Schedule H',
            self::H1 => 'Schedule H1',
            self::X => 'Schedule X',
            self::G => 'Schedule G',
            self::OTC => 'OTC',
        };
    }

    public function requiresPrescription(): bool
    {
        return match ($this) {
            self::H, self::H1, self::X => true,
            self::G, self::OTC => false,
        };
    }
}
