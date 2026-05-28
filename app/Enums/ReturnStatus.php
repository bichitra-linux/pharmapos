<?php

declare(strict_types=1);

namespace App\Enums;

enum ReturnStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }
}
