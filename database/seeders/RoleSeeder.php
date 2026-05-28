<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Roles are stored as simple strings in the users.role column
        // No separate table needed. Valid roles:
        // - owner: Full access, company owner
        // - admin: Administrative access
        // - pharmacist: Can dispense prescriptions, manage medicines
        // - cashier: POS operations, sales
        // - inventory_staff: Stock management, purchases
    }
}
