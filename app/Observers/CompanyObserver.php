<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Company;

class CompanyObserver extends AuditableObserver
{
    public static function modelClass(): string
    {
        return Company::class;
    }
}
