<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;

class SaleObserver extends AuditableObserver
{
    public static function modelClass(): string
    {
        return Sale::class;
    }
}
