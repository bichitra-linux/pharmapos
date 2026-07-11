<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class TenantPolicy
{
    use HandlesAuthorization;

    protected function sameCompany(User $user, mixed $resource): bool
    {
        if ($resource === null) {
            return false;
        }

        $companyId = $resource->company_id ?? null;

        if ($companyId === null) {
            return false;
        }

        return (int) $user->company_id === (int) $companyId;
    }

    protected function permits(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }
}
