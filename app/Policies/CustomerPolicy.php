<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Customer;

class CustomerPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->sameCompany($user, $customer) && $this->permits($user, 'customers.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->sameCompany($user, $customer) && $this->permits($user, 'customers.edit');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->sameCompany($user, $customer) && $this->permits($user, 'customers.delete');
    }
}
