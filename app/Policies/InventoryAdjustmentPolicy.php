<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InventoryAdjustment;
use App\Models\User;

class InventoryAdjustmentPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'inventory.view');
    }

    public function view(User $user, InventoryAdjustment $adjustment): bool
    {
        return $this->sameCompany($user, $adjustment) && $this->permits($user, 'inventory.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'inventory.create');
    }

    public function update(User $user, InventoryAdjustment $adjustment): bool
    {
        return $this->sameCompany($user, $adjustment) && $this->permits($user, 'inventory.edit');
    }

    public function delete(User $user, InventoryAdjustment $adjustment): bool
    {
        return $this->sameCompany($user, $adjustment) && $this->permits($user, 'inventory.delete');
    }
}
