<?php

declare(strict_types=1);

namespace App\Enums;

enum UnitType: string
{
    case Strip = 'strip';
    case Bottle = 'bottle';
    case Tube = 'tube';
    case Piece = 'piece';
    case Box = 'box';
    case Vial = 'vial';
    case Sachet = 'sachet';
    case Roll = 'roll';

    public function label(): string
    {
        return match ($this) {
            self::Strip => 'Strip',
            self::Bottle => 'Bottle',
            self::Tube => 'Tube',
            self::Piece => 'Piece',
            self::Box => 'Box',
            self::Vial => 'Vial',
            self::Sachet => 'Sachet',
            self::Roll => 'Roll',
        };
    }
}
