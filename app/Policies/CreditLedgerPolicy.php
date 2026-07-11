<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CreditLedger;
use App\Models\User;

class CreditLedgerPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'customers.view');
    }

    public function view(User $user, CreditLedger $ledger): bool
    {
        return $this->sameCompany($user, $ledger) && $this->permits($user, 'customers.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'customers.credit');
    }
}
