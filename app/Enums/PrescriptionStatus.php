<?php

declare(strict_types=1);

namespace App\Enums;

enum PrescriptionStatus: string
{
    case Pending = 'pending';
    case Dispensed = 'dispensed';
    case Partial = 'partial';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Dispensed => 'Dispensed',
            self::Partial => 'Partial',
            self::Cancelled => 'Cancelled',
        };
    }
}
