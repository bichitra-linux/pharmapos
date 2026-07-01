<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaltCompositionSeeder extends Seeder
{
    public function run(): void
    {
        $company = DB::table('companies')->first();
        if (!$company) return;

        $salts = [
            ['name' => 'Paracetamol', 'description' => 'Analgesic and antipyretic', 'is_active' => true],
            ['name' => 'Amoxicillin', 'description' => 'Penicillin antibiotic', 'is_active' => true],
            ['name' => 'Metformin', 'description' => 'Antidiabetic', 'is_active' => true],
            ['name' => 'Amlodipine', 'description' => 'Calcium channel blocker for hypertension', 'is_active' => true],
            ['name' => 'Omeprazole', 'description' => 'Proton pump inhibitor', 'is_active' => true],
            ['name' => 'Ciprofloxacin', 'description' => 'Fluoroquinolone antibiotic', 'is_active' => true],
            ['name' => 'Azithromycin', 'description' => 'Macrolide antibiotic', 'is_active' => true],
            ['name' => 'Ibuprofen', 'description' => 'NSAID', 'is_active' => true],
            ['name' => 'Cetirizine', 'description' => 'Antihistamine', 'is_active' => true],
            ['name' => 'Ranitidine', 'description' => 'H2 blocker', 'is_active' => true],
            ['name' => 'Dolo', 'description' => 'Antipyretic', 'is_active' => true],
            ['name' => 'Losartan', 'description' => 'ARB for hypertension', 'is_active' => true],
            ['name' => 'Atorvastatin', 'description' => 'Statin for cholesterol', 'is_active' => true],
            ['name' => 'Pantoprazole', 'description' => 'Proton pump inhibitor', 'is_active' => true],
            ['name' => 'Clopidogrel', 'description' => 'Antiplatelet', 'is_active' => true],
            ['name' => 'Metoprolol', 'description' => 'Beta blocker', 'is_active' => true],
            ['name' => 'Glibenclamide', 'description' => 'Sulfonylurea antidiabetic', 'is_active' => true],
            ['name' => 'Glimepiride', 'description' => 'Sulfonylurea antidiabetic', 'is_active' => true],
            ['name' => 'Sitagliptin', 'description' => 'DPP-4 inhibitor', 'is_active' => true],
            ['name' => 'Empagliflozin', 'description' => 'SGLT2 inhibitor', 'is_active' => true],
            ['name' => 'Levothyroxine', 'description' => 'Thyroid hormone', 'is_active' => true],
            ['name' => 'Salbutamol', 'description' => 'Bronchodilator', 'is_active' => true],
            ['name' => 'Budesonide', 'description' => 'Corticosteroid inhaler', 'is_active' => true],
            ['name' => 'Montelukast', 'description' => 'Leukotriene receptor antagonist', 'is_active' => true],
            ['name' => 'Diclofenac', 'description' => 'NSAID', 'is_active' => true],
            ['name' => 'Aceclofenac', 'description' => 'NSAID', 'is_active' => true],
            ['name' => 'Piroxicam', 'description' => 'NSAID', 'is_active' => true],
            ['name' => 'Naproxen', 'description' => 'NSAID', 'is_active' => true],
            ['name' => 'Tramadol', 'description' => 'Opioid analgesic', 'is_active' => true],
            ['name' => 'Codeine', 'description' => 'Opioid analgesic', 'is_active' => true],
            ['name' => 'Fluoxetine', 'description' => 'SSRI antidepressant', 'is_active' => true],
            ['name' => 'Sertraline', 'description' => 'SSRI antidepressant', 'is_active' => true],
            ['name' => 'Amitriptyline', 'description' => 'Tricyclic antidepressant', 'is_active' => true],
            ['name' => 'Diazepam', 'description' => 'Benzodiazepine', 'is_active' => true],
            ['name' => 'Alprazolam', 'description' => 'Benzodiazepine', 'is_active' => true],
            ['name' => 'Phenobarbital', 'description' => 'Barbiturate anticonvulsant', 'is_active' => true],
            ['name' => 'Carbamazepine', 'description' => 'Anticonvulsant', 'is_active' => true],
            ['name' => 'Valproic Acid', 'description' => 'Anticonvulsant', 'is_active' => true],
            ['name' => 'Cefixime', 'description' => 'Cephalosporin antibiotic', 'is_active' => true],
            ['name' => 'Cefuroxime', 'description' => 'Cephalosporin antibiotic', 'is_active' => true],
            ['name' => 'Amoxicillin + Clavulanic Acid', 'description' => 'Penicillin with beta-lactamase inhibitor', 'is_active' => true],
            ['name' => 'Metronidazole', 'description' => 'Antiprotozoal and antibacterial', 'is_active' => true],
            ['name' => 'Albendazole', 'description' => 'Anthelmintic', 'is_active' => true],
            ['name' => 'Mebendazole', 'description' => 'Anthelmintic', 'is_active' => true],
            ['name' => 'ORS', 'description' => 'Oral rehydration salts', 'is_active' => true],
            ['name' => 'Zinc Sulfate', 'description' => 'Mineral supplement', 'is_active' => true],
            ['name' => 'Iron + Folic Acid', 'description' => 'Hematinic supplement', 'is_active' => true],
            ['name' => 'Calcium Carbonate + Vitamin D3', 'description' => 'Bone health supplement', 'is_active' => true],
            ['name' => 'Multivitamin', 'description' => 'Vitamin supplement', 'is_active' => true],
            ['name' => 'Vitamin C', 'description' => 'Ascorbic acid supplement', 'is_active' => true],
        ];

        $now = now();
        foreach ($salts as &$salt) {
            $salt['company_id'] = $company->id;
            $salt['created_at'] = $now;
            $salt['updated_at'] = $now;
        }

        DB::table('salt_compositions')->insert($salts);
    }
}
