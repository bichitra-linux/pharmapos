<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Register;

class RegisterPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'register.view');
    }

    public function view(User $user, Register $register): bool
    {
        return $this->sameCompany($user, $register) && $this->permits($user, 'register.view');
    }

    public function open(User $user): bool
    {
        return $this->permits($user, 'register.operate');
    }

    public function close(User $user, Register $register): bool
    {
        return $this->sameCompany($user, $register) && $this->permits($user, 'register.operate');
    }
}
