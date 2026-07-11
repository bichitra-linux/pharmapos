<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\CustomerReturn;

class CustomerReturnPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'returns.view');
    }

    public function view(User $user, CustomerReturn $return): bool
    {
        return $this->sameCompany($user, $return) && $this->permits($user, 'returns.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'returns.create');
    }

    public function update(User $user, CustomerReturn $return): bool
    {
        return $this->sameCompany($user, $return) && $this->permits($user, 'returns.edit');
    }

    public function delete(User $user, CustomerReturn $return): bool
    {
        return $this->sameCompany($user, $return) && $this->permits($user, 'returns.delete');
    }
}
