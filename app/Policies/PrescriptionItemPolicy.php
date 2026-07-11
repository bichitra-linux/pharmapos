<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\PrescriptionItem;

class PrescriptionItemPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'prescriptions.view');
    }

    public function view(User $user, PrescriptionItem $item): bool
    {
        return $this->sameCompany($user, $item) && $this->permits($user, 'prescriptions.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'prescriptions.create');
    }

    public function update(User $user, PrescriptionItem $item): bool
    {
        return $this->sameCompany($user, $item) && $this->permits($user, 'prescriptions.edit');
    }

    public function delete(User $user, PrescriptionItem $item): bool
    {
        return $this->sameCompany($user, $item) && $this->permits($user, 'prescriptions.delete');
    }
}
