<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $this->sameCompany($user, $target) && $this->permits($user, 'users.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $this->sameCompany($user, $target) && $this->permits($user, 'users.edit');
    }

    public function delete(User $user, User $target): bool
    {
        return $this->sameCompany($user, $target) && $this->permits($user, 'users.delete');
    }
}
