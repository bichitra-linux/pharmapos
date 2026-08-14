<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'purchases.view');
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $this->sameCompany($user, $purchase) && $this->permits($user, 'purchases.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'purchases.create');
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $this->sameCompany($user, $purchase) && $this->permits($user, 'purchases.edit');
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $this->sameCompany($user, $purchase) && $this->permits($user, 'purchases.delete');
    }
}
