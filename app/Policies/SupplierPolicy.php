<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Supplier;

class SupplierPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'suppliers.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $this->sameCompany($user, $supplier) && $this->permits($user, 'suppliers.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'suppliers.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->sameCompany($user, $supplier) && $this->permits($user, 'suppliers.edit');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->sameCompany($user, $supplier) && $this->permits($user, 'suppliers.delete');
    }
}
