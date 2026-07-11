<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CreditLedger;

class CreditLedgerObserver extends AuditableObserver
{
    public static function modelClass(): string
    {
        return CreditLedger::class;
    }
}
