<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManufacturerSeeder extends Seeder
{
    public function run(): void
    {
        // Manufacturers are created per-company. This seeder creates default manufacturers
        // for the first company. For multi-tenant, use CompanyCreated event.

        $manufacturers = [
            ['name' => 'Nepal Pharmaceutical Laboratory Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Lomus Pharmaceuticals Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Deurali-Janta Pharmaceuticals Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Quest Pharmaceuticals Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Nepal Remedies Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Midas Pharmaceuticals Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Cosmos Pharmaceuticals Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Aristo Pharmaceuticals Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Iris Pharma Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Asian Pharmaceuticals Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Capt. Pawan Pharma Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Sunrise Pharmaceuticals Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Mediplant Healthcare Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Shree Pharmaceuticals Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Vibor Pharma Pvt. Ltd.', 'country' => 'Nepal'],
            ['name' => 'Cipla Ltd.', 'country' => 'India'],
            ['name' => 'Sun Pharmaceutical Industries Ltd.', 'country' => 'India'],
            ['name' => 'Dr. Reddy\'s Laboratories Ltd.', 'country' => 'India'],
            ['name' => 'Lupin Ltd.', 'country' => 'India'],
            ['name' => 'Zydus Lifesciences Ltd.', 'country' => 'India'],
        ];

        $now = now();
        foreach ($manufacturers as &$manufacturer) {
            $manufacturer['company_id'] = 1;
            $manufacturer['is_active'] = true;
            $manufacturer['created_at'] = $now;
            $manufacturer['updated_at'] = $now;
        }

        DB::table('manufacturers')->insert($manufacturers);
    }
}
