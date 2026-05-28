<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Pharmacist = 'pharmacist';
    case Cashier = 'cashier';
    case InventoryStaff = 'inventory_staff';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Pharmacist => 'Pharmacist',
            self::Cashier => 'Cashier',
            self::InventoryStaff => 'Inventory Staff',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::Owner => ['*'],
            self::Admin => [
                'sales.*', 'purchases.*', 'inventory.*', 'customers.*',
                'suppliers.*', 'reports.*', 'prescriptions.*', 'users.view',
            ],
            self::Pharmacist => [
                'sales.*', 'prescriptions.*', 'inventory.view',
                'customers.view', 'medicines.view',
            ],
            self::Cashier => [
                'sales.create', 'sales.view', 'customers.view',
                'payments.*',
            ],
            self::InventoryStaff => [
                'inventory.*', 'purchases.view', 'suppliers.view',
            ],
        };
    }
}
