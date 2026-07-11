<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'sales.view');
    }

    public function view(User $user, Sale $sale): bool
    {
        return $this->sameCompany($user, $sale) && $this->permits($user, 'sales.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'sales.create');
    }

    public function update(User $user, Sale $sale): bool
    {
        return $this->sameCompany($user, $sale) && $this->permits($user, 'sales.edit');
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $this->sameCompany($user, $sale) && $this->permits($user, 'sales.delete');
    }

    public function refund(User $user, Sale $sale): bool
    {
        return $this->sameCompany($user, $sale) && $this->permits($user, 'sales.refund');
    }
}
