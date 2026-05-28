<?php

declare(strict_types=1);

namespace App\Enums;

enum AdjustmentType: string
{
    case Damage = 'damage';
    case Expiry = 'expiry';
    case CountAdjustment = 'count_adjustment';
    case Return = 'return';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Damage => 'Damage',
            self::Expiry => 'Expiry',
            self::CountAdjustment => 'Count Adjustment',
            self::Return => 'Return',
            self::Other => 'Other',
        };
    }
}
