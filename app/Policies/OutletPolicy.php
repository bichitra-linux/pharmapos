<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Outlet;

class OutletPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'outlets.view');
    }

    public function view(User $user, Outlet $outlet): bool
    {
        return $this->sameCompany($user, $outlet) && $this->permits($user, 'outlets.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'outlets.create');
    }

    public function update(User $user, Outlet $outlet): bool
    {
        return $this->sameCompany($user, $outlet) && $this->permits($user, 'outlets.edit');
    }

    public function delete(User $user, Outlet $outlet): bool
    {
        return $this->sameCompany($user, $outlet) && $this->permits($user, 'outlets.delete');
    }
}
