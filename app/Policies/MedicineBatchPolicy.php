<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\MedicineBatch;

class MedicineBatchPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'medicines.view');
    }

    public function view(User $user, MedicineBatch $batch): bool
    {
        return $this->sameCompany($user, $batch) && $this->permits($user, 'medicines.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'medicines.create');
    }

    public function update(User $user, MedicineBatch $batch): bool
    {
        return $this->sameCompany($user, $batch) && $this->permits($user, 'medicines.edit');
    }

    public function delete(User $user, MedicineBatch $batch): bool
    {
        return $this->sameCompany($user, $batch) && $this->permits($user, 'medicines.delete');
    }
}
