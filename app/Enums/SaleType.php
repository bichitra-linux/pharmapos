<?php

declare(strict_types=1);

namespace App\Enums;

enum SaleType: string
{
    case WalkIn = 'walk_in';
    case Online = 'online';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::WalkIn => 'Walk-in',
            self::Online => 'Online',
            self::Delivery => 'Delivery',
        };
    }
}
