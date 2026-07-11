<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Prescription;

class PrescriptionPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'prescriptions.view');
    }

    public function view(User $user, Prescription $prescription): bool
    {
        return $this->sameCompany($user, $prescription) && $this->permits($user, 'prescriptions.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'prescriptions.create');
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $this->sameCompany($user, $prescription) && $this->permits($user, 'prescriptions.edit');
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $this->sameCompany($user, $prescription) && $this->permits($user, 'prescriptions.delete');
    }

    public function dispense(User $user, Prescription $prescription): bool
    {
        return $this->sameCompany($user, $prescription) && $user->canDispense();
    }
}
