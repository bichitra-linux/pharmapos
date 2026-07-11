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
                'suppliers.*', 'reports.*', 'prescriptions.*', 'returns.*',
                'outlets.*', 'settings.edit', 'users.*', 'register.operate',
                'manage_billing',
            ],
            self::Pharmacist => [
                'sales.*', 'prescriptions.*', 'inventory.*',
                'customers.view', 'medicines.*', 'register.operate',
            ],
            self::Cashier => [
                'sales.create', 'sales.view', 'customers.view',
                'payments.*', 'inventory.view', 'medicines.view',
            ],
            self::InventoryStaff => [
                'inventory.*', 'purchases.*', 'suppliers.view',
                'medicines.view', 'returns.view',
            ],
        };
    }
}
