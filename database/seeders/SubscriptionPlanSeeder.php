<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'price_monthly' => 1500.00,
                'price_yearly' => 15000.00,
                'max_outlets' => 1,
                'max_users' => 3,
                'max_medicines' => 1000,
                'features' => json_encode([
                    'Basic POS',
                    'Inventory Management',
                    'Sales Reports',
                    'Customer Management',
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Professional',
                'price_monthly' => 3500.00,
                'price_yearly' => 35000.00,
                'max_outlets' => 3,
                'max_users' => 10,
                'max_medicines' => 5000,
                'features' => json_encode([
                    'Everything in Starter',
                    'Multi-outlet Support',
                    'Prescription Management',
                    'Supplier Management',
                    'Advanced Reports',
                    'Narcotics Register',
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Enterprise',
                'price_monthly' => 7000.00,
                'price_yearly' => 70000.00,
                'max_outlets' => 999,
                'max_users' => 999,
                'max_medicines' => 999999,
                'features' => json_encode([
                    'Everything in Professional',
                    'Unlimited Outlets',
                    'Unlimited Users',
                    'API Access',
                    'Priority Support',
                    'Custom Reports',
                    'Multi-company Support',
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('subscription_plans')->insert($plans);
    }
}
