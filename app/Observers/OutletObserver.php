<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Outlet;

class OutletObserver extends AuditableObserver
{
    public static function modelClass(): string
    {
        return Outlet::class;
    }
}
