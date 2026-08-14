<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            SubscriptionPlanSeeder::class,
            CompanySeeder::class,
            SaltCompositionSeeder::class,
            MedicineCategorySeeder::class,
            ManufacturerSeeder::class,
            SuperAdminSeeder::class,
            LandingPageSeeder::class,
            PaymentGatewaySeeder::class,
        ];

        if (! app()->isProduction()) {
            $seeders[] = DummyDataSeeder::class;
        }

        $this->call($seeders);
    }
}
