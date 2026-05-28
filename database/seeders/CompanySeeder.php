<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Create demo company
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Demo Pharmacy',
            'slug' => 'demo-pharmacy',
            'address' => 'Kathmandu, Nepal',
            'phone' => '+977-9841234567',
            'email' => 'admin@demopharmacy.com',
            'pan_number' => '123456789',
            'vat_number' => 'VAT-123456',
            'drug_license_number' => 'DL-NP-2024-001',
            'pharmacy_license_number' => 'PH-NP-2024-001',
            'pharmacist_name' => 'Ram Sharma',
            'pharmacist_registration_number' => 'NPhC-2024-001',
            'subscription_plan_id' => 1, // Starter plan
            'subscription_expires_at' => now()->addYear(),
            'settings' => json_encode([
                'currency' => 'NPR',
                'currency_symbol' => 'रू',
                'timezone' => 'Asia/Kathmandu',
                'date_format' => 'YYYY/MM/DD',
                'vat_rate' => 13,
                'language' => 'en',
            ]),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create main outlet
        $outletId = DB::table('outlets')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Main Branch',
            'address' => 'Kathmandu, Nepal',
            'phone' => '+977-9841234567',
            'drug_license_number' => 'DL-NP-2024-001',
            'is_main_outlet' => true,
            'is_active' => true,
            'settings' => json_encode([]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create admin user
        DB::table('users')->insert([
            'company_id' => $companyId,
            'outlet_id' => $outletId,
            'name' => 'Admin',
            'email' => 'admin@demopharmacy.com',
            'password' => Hash::make('password'),
            'phone' => '+977-9841234567',
            'role' => 'owner',
            'pharmacist_registration' => 'NPhC-2024-001',
            'permissions' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create default payment methods for the company
        $paymentMethods = [
            ['name' => 'Cash', 'type' => 'cash', 'gateway' => null],
            ['name' => 'eSewa', 'type' => 'digital_wallet', 'gateway' => 'esewa'],
            ['name' => 'Khalti', 'type' => 'digital_wallet', 'gateway' => 'khalti'],
            ['name' => 'IME Pay', 'type' => 'digital_wallet', 'gateway' => 'ime_pay'],
            ['name' => 'Fonepay', 'type' => 'digital_wallet', 'gateway' => 'fonepay'],
            ['name' => 'ConnectIPS', 'type' => 'bank_transfer', 'gateway' => 'connectips'],
            ['name' => 'Card', 'type' => 'card', 'gateway' => null],
            ['name' => 'Bank Transfer', 'type' => 'bank_transfer', 'gateway' => null],
        ];

        foreach ($paymentMethods as $method) {
            DB::table('payment_methods')->insert([
                'company_id' => $companyId,
                'name' => $method['name'],
                'type' => $method['type'],
                'gateway' => $method['gateway'],
                'is_active' => true,
                'config' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
