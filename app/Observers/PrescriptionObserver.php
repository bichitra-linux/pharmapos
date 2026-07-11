<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Prescription;
use App\Models\PrescriptionItem;

class PrescriptionObserver extends AuditableObserver
{
    public static function modelClass(): string
    {
        return Prescription::class;
    }
}
