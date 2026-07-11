<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PrescriptionItem;

class PrescriptionItemObserver extends AuditableObserver
{
    public static function modelClass(): string
    {
        return PrescriptionItem::class;
    }
}
