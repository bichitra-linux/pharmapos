<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CustomerReturn;
use App\Models\CustomerReturnItem;

class CustomerReturnObserver extends AuditableObserver
{
    public static function modelClass(): string
    {
        return CustomerReturn::class;
    }
}
