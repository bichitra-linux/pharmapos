<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy extends TenantPolicy
{
    public function view(User $user, Company $company): bool
    {
        return $this->sameCompany($user, $company) && $this->permits($user, 'settings.view');
    }

    public function update(User $user, Company $company): bool
    {
        return $this->sameCompany($user, $company) && $this->permits($user, 'settings.edit');
    }
}
