<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\InventoryAdjustment;
use App\Models\AdjustmentItem;

class InventoryAdjustmentObserver extends AuditableObserver
{
    public static function modelClass(): string
    {
        return InventoryAdjustment::class;
    }
}
