<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case Partial = 'partial';
    case Due = 'due';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::Partial => 'Partial',
            self::Due => 'Due',
            self::Refunded => 'Refunded',
        };
    }
}
