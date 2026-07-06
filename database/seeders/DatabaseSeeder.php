<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SubscriptionPlanSeeder::class,
            CompanySeeder::class,
            SaltCompositionSeeder::class,
            MedicineCategorySeeder::class,
            ManufacturerSeeder::class,
            SuperAdminSeeder::class,
            LandingPageSeeder::class,
            DummyDataSeeder::class,
        ]);
    }
}
