<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicineCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Categories are created per-company. This seeder creates default categories
        // for the first company. For multi-tenant, use CompanyCreated event.
        // For now, we'll insert with company_id = 1 as a template.

        $categories = [
            ['name' => 'Analgesics', 'description' => 'Pain relievers and anti-inflammatory drugs'],
            ['name' => 'Antibiotics', 'description' => 'Antibacterial medications'],
            ['name' => 'Antidiabetics', 'description' => 'Diabetes management medications'],
            ['name' => 'Cardiovascular', 'description' => 'Heart and blood pressure medications'],
            ['name' => 'Gastrointestinal', 'description' => 'Digestive system medications'],
            ['name' => 'Respiratory', 'description' => 'Respiratory system medications'],
            ['name' => 'Antihistamines', 'description' => 'Allergy medications'],
            ['name' => 'Vitamins & Supplements', 'description' => 'Nutritional supplements'],
            ['name' => 'Dermatology', 'description' => 'Skin care medications'],
            ['name' => 'Ophthalmology', 'description' => 'Eye care medications'],
            ['name' => 'ENT', 'description' => 'Ear, nose, and throat medications'],
            ['name' => 'Anti-infectives', 'description' => 'Antifungal, antiviral, antiparasitic'],
            ['name' => 'Hormones', 'description' => 'Hormonal medications'],
            ['name' => 'Psychiatric', 'description' => 'Mental health medications'],
            ['name' => 'Neurological', 'description' => 'Nervous system medications'],
            ['name' => 'Musculoskeletal', 'description' => 'Bone and joint medications'],
            ['name' => 'Urological', 'description' => 'Urinary system medications'],
            ['name' => 'Gynecological', 'description' => 'Women\'s health medications'],
            ['name' => 'Pediatric', 'description' => 'Children\'s medications'],
            ['name' => 'Surgical', 'description' => 'Surgical supplies and dressings'],
        ];

        $now = now();
        foreach ($categories as &$category) {
            $category['company_id'] = 1;
            $category['parent_id'] = null;
            $category['is_active'] = true;
            $category['created_at'] = $now;
            $category['updated_at'] = $now;
        }

        DB::table('medicine_categories')->insert($categories);
    }
}
