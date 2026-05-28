<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    private int $companyId = 1;
    private int $outletId = 1;
    private int $userId = 1;

    public function run(): void
    {
        $now = now();
        $companyId = $this->companyId;
        $outletId = $this->outletId;

        // ── Customers ──────────────────────────────────────────────
        $customers = [];
        $customerData = [
            ['name' => 'Sita Sharma', 'phone' => '9841000001', 'gender' => 'female', 'dob' => '1985-03-15'],
            ['name' => 'Ram Bahadur Thapa', 'phone' => '9841000002', 'gender' => 'male', 'dob' => '1978-07-22'],
            ['name' => 'Gita Poudel', 'phone' => '9841000003', 'gender' => 'female', 'dob' => '1990-11-05'],
            ['name' => 'Hari Prasad Koirala', 'phone' => '9841000004', 'gender' => 'male', 'dob' => '1965-01-30'],
            ['name' => 'Sunita Maharjan', 'phone' => '9841000005', 'gender' => 'female', 'dob' => '1992-06-18'],
            ['name' => 'Bikash Gurung', 'phone' => '9841000006', 'gender' => 'male', 'dob' => '1988-09-12'],
            ['name' => 'Anita Shrestha', 'phone' => '9841000007', 'gender' => 'female', 'dob' => '1975-04-25'],
            ['name' => 'Deepak Tamang', 'phone' => '9841000008', 'gender' => 'male', 'dob' => '1995-12-08'],
            ['name' => 'Kamala Bhandari', 'phone' => '9841000009', 'gender' => 'female', 'dob' => '1982-08-14'],
            ['name' => 'Rajesh Adhikari', 'phone' => '9841000010', 'gender' => 'male', 'dob' => '1970-02-28'],
            ['name' => 'Mina Sapkota', 'phone' => '9841000011', 'gender' => 'female', 'dob' => '1998-05-20'],
            ['name' => 'Suresh Khadka', 'phone' => '9841000012', 'gender' => 'male', 'dob' => '1960-10-10'],
        ];

        foreach ($customerData as $c) {
            $customers[] = DB::table('customers')->insertGetId([
                'company_id' => $companyId,
                'name' => $c['name'],
                'phone' => $c['phone'],
                'email' => strtolower(str_replace(' ', '.', $c['name'])) . '@example.com',
                'address' => 'Kathmandu, Nepal',
                'date_of_birth' => $c['dob'],
                'gender' => $c['gender'],
                'allergies' => null,
                'chronic_conditions' => null,
                'loyalty_points' => rand(0, 500),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── Suppliers ──────────────────────────────────────────────
        $suppliers = [];
        $supplierData = [
            ['name' => 'Nepal Medical Distributors', 'contact' => 'Ramesh Shrestha', 'phone' => '9851000001'],
            ['name' => 'Himalaya Pharma Trading', 'contact' => 'Bijay Lama', 'phone' => '9851000002'],
            ['name' => 'Kathmandu Drug Agency', 'contact' => 'Sanjay Karki', 'phone' => '9851000003'],
            ['name' => 'Everest Pharmaceuticals Supply', 'contact' => 'Pradeep Joshi', 'phone' => '9851000004'],
            ['name' => 'Sagarmatha Medical Hall', 'contact' => 'Ashok Thapa', 'phone' => '9851000005'],
            ['name' => 'Ganesh Pharma Wholesalers', 'contact' => 'Dipak Rai', 'phone' => '9851000006'],
            ['name' => 'Lumbini Drug Distributors', 'contact' => 'Krishna Yadav', 'phone' => '9851000007'],
            ['name' => 'Janakpur Medical Supply', 'contact' => 'Raj Kumar Mahato', 'phone' => '9851000008'],
            ['name' => 'Pokhara Pharma Traders', 'contact' => 'Mohan Gurung', 'phone' => '9851000009'],
            ['name' => 'Birat Medical Distributors', 'contact' => 'Santosh Limbu', 'phone' => '9851000010'],
            ['name' => 'Butwal Drug House', 'contact' => 'Tek Bahadur Chaudhary', 'phone' => '9851000011'],
            ['name' => 'Nepalgunj Pharma Agency', 'contact' => 'Farid Khan', 'phone' => '9851000012'],
        ];

        foreach ($supplierData as $s) {
            $suppliers[] = DB::table('suppliers')->insertGetId([
                'company_id' => $companyId,
                'name' => $s['name'],
                'contact_person' => $s['contact'],
                'phone' => $s['phone'],
                'email' => strtolower(str_replace(' ', '.', $s['name'])) . '@supplier.com',
                'address' => 'Kathmandu, Nepal',
                'pan_number' => 'PAN' . rand(10000000, 99999999),
                'drug_license_number' => 'DL-SUP-' . str_pad((string) (count($suppliers) + 1), 3, '0', STR_PAD_LEFT),
                'payment_terms' => 'Net 30',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── Medicines ──────────────────────────────────────────────
        $medicines = [];
        $medicineData = [
            ['brand' => 'Paracetamol 500mg', 'generic' => 'Paracetamol', 'salt' => 1, 'cat' => 1, 'mfg' => 1, 'form' => 'tablet', 'strength' => '500mg', 'unit' => 'strip', 'schedule' => 'OTC'],
            ['brand' => 'Amoxicillin 250mg', 'generic' => 'Amoxicillin', 'salt' => 2, 'cat' => 2, 'mfg' => 2, 'form' => 'capsule', 'strength' => '250mg', 'unit' => 'strip', 'schedule' => 'H'],
            ['brand' => 'Metformin 500mg', 'generic' => 'Metformin', 'salt' => 3, 'cat' => 3, 'mfg' => 3, 'form' => 'tablet', 'strength' => '500mg', 'unit' => 'strip', 'schedule' => 'H'],
            ['brand' => 'Amlodipine 5mg', 'generic' => 'Amlodipine', 'salt' => 4, 'cat' => 4, 'mfg' => 4, 'form' => 'tablet', 'strength' => '5mg', 'unit' => 'strip', 'schedule' => 'H'],
            ['brand' => 'Omeprazole 20mg', 'generic' => 'Omeprazole', 'salt' => 5, 'cat' => 5, 'mfg' => 5, 'form' => 'capsule', 'strength' => '20mg', 'unit' => 'strip', 'schedule' => 'OTC'],
            ['brand' => 'Cetirizine 10mg', 'generic' => 'Cetirizine', 'salt' => 6, 'cat' => 7, 'mfg' => 6, 'form' => 'tablet', 'strength' => '10mg', 'unit' => 'strip', 'schedule' => 'OTC'],
            ['brand' => 'Azithromycin 500mg', 'generic' => 'Azithromycin', 'salt' => 7, 'cat' => 2, 'mfg' => 7, 'form' => 'tablet', 'strength' => '500mg', 'unit' => 'strip', 'schedule' => 'H'],
            ['brand' => 'Ibuprofen 400mg', 'generic' => 'Ibuprofen', 'salt' => 8, 'cat' => 1, 'mfg' => 8, 'form' => 'tablet', 'strength' => '400mg', 'unit' => 'strip', 'schedule' => 'OTC'],
            ['brand' => 'Losartan 50mg', 'generic' => 'Losartan', 'salt' => 9, 'cat' => 4, 'mfg' => 9, 'form' => 'tablet', 'strength' => '50mg', 'unit' => 'strip', 'schedule' => 'H'],
            ['brand' => 'Pantoprazole 40mg', 'generic' => 'Pantoprazole', 'salt' => 10, 'cat' => 5, 'mfg' => 10, 'form' => 'tablet', 'strength' => '40mg', 'unit' => 'strip', 'schedule' => 'OTC'],
            ['brand' => 'Ciprofloxacin 500mg', 'generic' => 'Ciprofloxacin', 'salt' => 11, 'cat' => 2, 'mfg' => 1, 'form' => 'tablet', 'strength' => '500mg', 'unit' => 'strip', 'schedule' => 'H'],
            ['brand' => 'Diclofenac Gel', 'generic' => 'Diclofenac', 'salt' => 12, 'cat' => 1, 'mfg' => 2, 'form' => 'gel', 'strength' => '1%', 'unit' => 'tube', 'schedule' => 'OTC'],
        ];

        foreach ($medicineData as $m) {
            $medicines[] = DB::table('medicines')->insertGetId([
                'company_id' => $companyId,
                'generic_name' => $m['generic'],
                'brand_name' => $m['brand'],
                'manufacturer_id' => $m['mfg'],
                'salt_composition_id' => $m['salt'],
                'medicine_category_id' => $m['cat'],
                'dosage_form' => $m['form'],
                'strength' => $m['strength'],
                'unit_type' => $m['unit'],
                'units_per_pack' => $m['unit'] === 'strip' ? 10 : 1,
                'schedule_type' => $m['schedule'],
                'hsn_code' => 'HSN' . rand(1000, 9999),
                'is_prescription_required' => $m['schedule'] === 'H',
                'is_active' => true,
                'barcode' => '89012345' . str_pad((string) count($medicines), 4, '0', STR_PAD_LEFT),
                'image' => null,
                'description' => $m['generic'] . ' ' . $m['strength'] . ' ' . $m['form'],
                'storage_conditions' => 'Store in a cool, dry place',
                'is_temperature_sensitive' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── Medicine Batches ───────────────────────────────────────
        $batches = [];
        foreach ($medicines as $idx => $medId) {
            for ($b = 1; $b <= 2; $b++) {
                $purchasePrice = rand(20, 500) + 0.50;
                $mrp = $purchasePrice * (1 + rand(20, 50) / 100);
                $sellingPrice = $mrp * 0.98;

                $batches[] = DB::table('medicine_batches')->insertGetId([
                    'company_id' => $companyId,
                    'medicine_id' => $medId,
                    'outlet_id' => $outletId,
                    'batch_number' => 'B' . str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT) . '-' . $b,
                    'manufacturing_date' => Carbon::now()->subMonths(rand(3, 12))->format('Y-m-d'),
                    'expiry_date' => Carbon::now()->addMonths(rand(6, 36))->format('Y-m-d'),
                    'quantity_in_stock' => rand(50, 500),
                    'purchase_price_per_unit' => round($purchasePrice, 2),
                    'mrp_per_unit' => round($mrp, 2),
                    'selling_price_per_unit' => round($sellingPrice, 2),
                    'barcode' => '89012345' . str_pad((string) $idx, 4, '0', STR_PAD_LEFT) . $b,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // ── Registers (Cash Registers) ─────────────────────────────
        $registers = [];
        for ($i = 0; $i < 10; $i++) {
            $openedAt = Carbon::now()->subDays($i);
            $registers[] = DB::table('registers')->insertGetId([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'user_id' => $this->userId,
                'opening_balance' => 5000.00,
                'closing_balance' => $i === 0 ? null : 5000.00 + rand(10000, 50000),
                'total_sales' => $i === 0 ? null : rand(10000, 50000),
                'total_returns' => $i === 0 ? null : rand(0, 2000),
                'total_cash' => $i === 0 ? null : rand(8000, 40000),
                'total_digital' => $i === 0 ? null : rand(2000, 10000),
                'status' => $i === 0 ? 'open' : 'closed',
                'opened_at' => $openedAt->format('Y-m-d H:i:s'),
                'closed_at' => $i === 0 ? null : $openedAt->addHours(10)->format('Y-m-d H:i:s'),
                'created_at' => $openedAt->format('Y-m-d H:i:s'),
                'updated_at' => $openedAt->format('Y-m-d H:i:s'),
            ]);
        }

        // ── Sales ──────────────────────────────────────────────────
        $sales = [];
        for ($i = 0; $i < 12; $i++) {
            $saleDate = Carbon::now()->subDays($i);
            $customerId = $i < 8 ? $customers[$i % count($customers)] : null;
            $subtotal = 0;
            $vatAmount = 0;
            $discountAmount = rand(0, 200);

            $invoiceNumber = 'INV-1-' . $saleDate->format('Ymd') . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);

            $saleId = DB::table('sales')->insertGetId([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'register_id' => $registers[$i % count($registers)],
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customerId,
                'prescription_id' => null,
                'sale_type' => 'walk_in',
                'subtotal' => 0,
                'discount_amount' => $discountAmount,
                'discount_type' => 'fixed',
                'vat_amount' => 0,
                'vat_percentage' => 13,
                'total_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'payment_status' => 'paid',
                'dispensed_by' => $this->userId,
                'notes' => null,
                'created_at' => $saleDate->format('Y-m-d H:i:s'),
                'updated_at' => $saleDate->format('Y-m-d H:i:s'),
            ]);

            // Create 2-4 sale items per sale
            $itemCount = rand(2, 4);
            $usedMedicines = [];
            for ($j = 0; $j < $itemCount; $j++) {
                $medIdx = ($i * 3 + $j) % count($medicines);
                if (in_array($medIdx, $usedMedicines)) continue;
                $usedMedicines[] = $medIdx;

                $batchIdx = $medIdx * 2;
                if (!isset($batches[$batchIdx])) continue;

                $batch = DB::table('medicine_batches')->where('id', $batches[$batchIdx])->first();
                if (!$batch) continue;

                $qty = rand(1, 5);
                $unitPrice = (float) $batch->selling_price_per_unit;
                $lineTotal = $unitPrice * $qty;
                $itemVat = ($lineTotal * 13) / 100;

                $subtotal += $lineTotal;
                $vatAmount += $itemVat;

                DB::table('sale_items')->insert([
                    'sale_id' => $saleId,
                    'medicine_id' => $medicines[$medIdx],
                    'batch_id' => $batches[$batchIdx],
                    'quantity' => $qty,
                    'unit_type' => 'strip',
                    'mrp' => $batch->mrp_per_unit,
                    'selling_price' => $unitPrice,
                    'discount' => 0,
                    'vat' => round($itemVat, 2),
                    'total' => round($lineTotal + $itemVat, 2),
                    'prescription_required' => false,
                    'created_at' => $saleDate->format('Y-m-d H:i:s'),
                    'updated_at' => $saleDate->format('Y-m-d H:i:s'),
                ]);

                // Deduct batch stock
                DB::table('medicine_batches')->where('id', $batches[$batchIdx])->decrement('quantity_in_stock', $qty);
            }

            $grandTotal = $subtotal + $vatAmount - $discountAmount;
            $paidAmount = $grandTotal;

            DB::table('sales')->where('id', $saleId)->update([
                'subtotal' => round($subtotal, 2),
                'vat_amount' => round($vatAmount, 2),
                'total_amount' => round($grandTotal, 2),
                'paid_amount' => round($paidAmount, 2),
            ]);

            // Create payment
            DB::table('sale_payments')->insert([
                'sale_id' => $saleId,
                'payment_method_id' => ($i % 3 === 1) ? 2 : 1, // Cash or eSewa
                'amount' => round($paidAmount, 2),
                'reference_number' => $i % 3 === 1 ? 'ESEWA-' . rand(100000, 999999) : null,
                'gateway_response' => null,
                'created_at' => $saleDate->format('Y-m-d H:i:s'),
            ]);

            $sales[] = $saleId;
        }

        // ── Purchases ──────────────────────────────────────────────
        $purchases = [];
        for ($i = 0; $i < 10; $i++) {
            $purchaseDate = Carbon::now()->subDays(rand(5, 60));
            $supplierId = $suppliers[$i % count($suppliers)];
            $subtotal = 0;
            $vatTotal = 0;

            $purchaseNumber = 'PO-1-' . $purchaseDate->format('Ymd') . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);

            $purchaseId = DB::table('purchases')->insertGetId([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'supplier_id' => $supplierId,
                'purchase_number' => $purchaseNumber,
                'purchase_date' => $purchaseDate->format('Y-m-d'),
                'grn_number' => 'GRN-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'subtotal' => 0,
                'discount' => 0,
                'vat' => 0,
                'total' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'payment_status' => 'paid',
                'status' => 'received',
                'notes' => null,
                'created_at' => $purchaseDate->format('Y-m-d H:i:s'),
                'updated_at' => $purchaseDate->format('Y-m-d H:i:s'),
            ]);

            $itemCount = rand(2, 4);
            for ($j = 0; $j < $itemCount; $j++) {
                $medIdx = ($i * 2 + $j) % count($medicines);
                $qty = rand(50, 200);
                $purchasePrice = rand(20, 300) + 0.50;
                $mrp = $purchasePrice * 1.4;
                $lineTotal = $qty * $purchasePrice;
                $itemVat = ($lineTotal * 13) / 100;

                $subtotal += $lineTotal;
                $vatTotal += $itemVat;

                DB::table('purchase_items')->insert([
                    'purchase_id' => $purchaseId,
                    'medicine_id' => $medicines[$medIdx],
                    'batch_number' => 'PB-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT) . '-' . ($j + 1),
                    'manufacturing_date' => $purchaseDate->subMonths(3)->format('Y-m-d'),
                    'expiry_date' => $purchaseDate->addMonths(24)->format('Y-m-d'),
                    'quantity' => $qty,
                    'purchase_price' => round($purchasePrice, 2),
                    'mrp' => round($mrp, 2),
                    'selling_price' => round($mrp * 0.98, 2),
                    'total' => round($lineTotal, 2),
                    'created_at' => $purchaseDate->format('Y-m-d H:i:s'),
                    'updated_at' => $purchaseDate->format('Y-m-d H:i:s'),
                ]);
            }

            $total = $subtotal + $vatTotal;
            DB::table('purchases')->where('id', $purchaseId)->update([
                'subtotal' => round($subtotal, 2),
                'vat' => round($vatTotal, 2),
                'total' => round($total, 2),
                'paid_amount' => round($total, 2),
            ]);

            $purchases[] = $purchaseId;
        }

        // ── Supplier Payments ──────────────────────────────────────
        for ($i = 0; $i < 10; $i++) {
            DB::table('supplier_payments')->insert([
                'company_id' => $companyId,
                'supplier_id' => $suppliers[$i % count($suppliers)],
                'purchase_id' => $purchases[$i % count($purchases)],
                'amount' => rand(5000, 50000) + 0.50,
                'payment_method' => $i % 2 === 0 ? 'bank_transfer' : 'cash',
                'reference_number' => 'PAY-' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'notes' => 'Payment for purchase order',
                'created_at' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
            ]);
        }

        // ── Prescriptions ──────────────────────────────────────────
        $prescriptions = [];
        $doctorNames = ['Dr. Anil Sharma', 'Dr. Sushila Koirala', 'Dr. Bikram Thapa', 'Dr. Nirmala Poudel', 'Dr. Rajesh Gupta', 'Dr. Kamal Bhattarai', 'Dr. Sabina Maharjan', 'Dr. Deepak Joshi', 'Dr. Anita Shrestha', 'Dr. Prakash Neupane'];
        $hospitals = ['TU Teaching Hospital', 'Bir Hospital', 'Patan Hospital', 'Nepal Medical College', 'Grande International Hospital', 'Norvic International Hospital', 'Mediciti Hospital', 'Om Hospital', 'National Hospital', 'Sumeru Hospital'];

        for ($i = 0; $i < 10; $i++) {
            $rxDate = Carbon::now()->subDays(rand(1, 30));
            $prescriptionId = DB::table('prescriptions')->insertGetId([
                'company_id' => $companyId,
                'customer_id' => $customers[$i % count($customers)],
                'prescription_number' => 'RX-' . $rxDate->format('Ymd') . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'doctor_name' => $doctorNames[$i],
                'hospital_name' => $hospitals[$i],
                'prescription_date' => $rxDate->format('Y-m-d'),
                'image_path' => null,
                'status' => $i < 3 ? 'pending' : ($i < 7 ? 'dispensed' : 'partial'),
                'notes' => null,
                'created_at' => $rxDate->format('Y-m-d H:i:s'),
                'updated_at' => $rxDate->format('Y-m-d H:i:s'),
            ]);

            // Prescription items
            for ($j = 0; $j < rand(2, 4); $j++) {
                $medIdx = ($i * 2 + $j) % count($medicines);
                DB::table('prescription_items')->insert([
                    'prescription_id' => $prescriptionId,
                    'medicine_name' => DB::table('medicines')->where('id', $medicines[$medIdx])->value('brand_name'),
                    'salt_composition_id' => $medicineData[$medIdx]['salt'],
                    'medicine_id' => $medicines[$medIdx],
                    'dosage' => rand(1, 2) . ' tablet' . (rand(0, 1) ? 's' : ''),
                    'frequency' => rand(1, 3) . ' times daily',
                    'duration' => rand(3, 14) . ' days',
                    'quantity_prescribed' => rand(10, 60),
                    'quantity_dispensed' => $i < 7 ? rand(10, 60) : rand(0, 30),
                    'notes' => null,
                    'created_at' => $rxDate->format('Y-m-d H:i:s'),
                    'updated_at' => $rxDate->format('Y-m-d H:i:s'),
                ]);
            }

            $prescriptions[] = $prescriptionId;
        }

        // ── Customer Returns ───────────────────────────────────────
        for ($i = 0; $i < 10; $i++) {
            $returnDate = Carbon::now()->subDays(rand(1, 20));
            $saleId = $sales[$i % count($sales)];
            $saleItems = DB::table('sale_items')->where('sale_id', $saleId)->get();

            if ($saleItems->isEmpty()) continue;

            $returnId = DB::table('customer_returns')->insertGetId([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'sale_id' => $saleId,
                'return_number' => 'CR-' . $returnDate->format('Ymd') . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'return_date' => $returnDate->format('Y-m-d'),
                'total_amount' => 0,
                'refund_amount' => 0,
                'refund_method' => 'cash',
                'reason' => ['Expired product', 'Wrong item', 'Customer changed mind', 'Damaged packaging', 'Quality issue'][rand(0, 4)],
                'processed_by' => $this->userId,
                'created_at' => $returnDate->format('Y-m-d H:i:s'),
                'updated_at' => $returnDate->format('Y-m-d H:i:s'),
            ]);

            $item = $saleItems->first();
            $returnQty = min(rand(1, 3), (int) $item->quantity);
            $returnAmount = $returnQty * (float) $item->selling_price;

            DB::table('customer_return_items')->insert([
                'return_id' => $returnId,
                'medicine_id' => $item->medicine_id,
                'batch_id' => $item->batch_id,
                'quantity' => $returnQty,
                'amount' => round($returnAmount, 2),
                'reason' => 'Return',
                'created_at' => $returnDate->format('Y-m-d H:i:s'),
            ]);

            DB::table('customer_returns')->where('id', $returnId)->update([
                'total_amount' => round($returnAmount, 2),
                'refund_amount' => round($returnAmount, 2),
            ]);

            // Restore batch stock
            DB::table('medicine_batches')->where('id', $item->batch_id)->increment('quantity_in_stock', $returnQty);
        }

        // ── Supplier Returns ───────────────────────────────────────
        for ($i = 0; $i < 10; $i++) {
            $returnDate = Carbon::now()->subDays(rand(1, 30));
            $purchaseId = $purchases[$i % count($purchases)];
            $purchaseItems = DB::table('purchase_items')->where('purchase_id', $purchaseId)->get();

            if ($purchaseItems->isEmpty()) continue;

            $supplierReturnId = DB::table('supplier_returns')->insertGetId([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'supplier_id' => $suppliers[$i % count($suppliers)],
                'purchase_id' => $purchaseId,
                'return_number' => 'SR-' . $returnDate->format('Ymd') . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'return_date' => $returnDate->format('Y-m-d'),
                'total_amount' => 0,
                'refund_status' => $i < 5 ? 'received' : 'pending',
                'reason' => ['Near expiry', 'Damaged goods', 'Wrong product', 'Quality issue', 'Overstocked'][rand(0, 4)],
                'created_at' => $returnDate->format('Y-m-d H:i:s'),
                'updated_at' => $returnDate->format('Y-m-d H:i:s'),
            ]);

            $item = $purchaseItems->first();
            $returnQty = rand(5, 20);
            $returnAmount = $returnQty * (float) $item->purchase_price;

            DB::table('supplier_return_items')->insert([
                'supplier_return_id' => $supplierReturnId,
                'medicine_id' => $item->medicine_id,
                'batch_id' => $batches[0], // dummy batch
                'quantity' => $returnQty,
                'amount' => round($returnAmount, 2),
                'reason' => 'Return to supplier',
                'created_at' => $returnDate->format('Y-m-d H:i:s'),
            ]);

            DB::table('supplier_returns')->where('id', $supplierReturnId)->update([
                'total_amount' => round($returnAmount, 2),
            ]);
        }

        // ── Inventory Adjustments ──────────────────────────────────
        $adjTypes = ['damage', 'expiry', 'count_adjustment', 'return', 'other'];
        $adjReasons = ['Water damage', 'Expired stock found', 'Physical count mismatch', 'Customer return processing', 'Reclassification'];

        for ($i = 0; $i < 10; $i++) {
            $adjDate = Carbon::now()->subDays(rand(1, 30));
            $type = $adjTypes[$i % 5];

            $adjId = DB::table('inventory_adjustments')->insertGetId([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'type' => $type,
                'reason' => $adjReasons[$i % 5],
                'adjusted_by' => $this->userId,
                'created_at' => $adjDate->format('Y-m-d H:i:s'),
                'updated_at' => $adjDate->format('Y-m-d H:i:s'),
            ]);

            $batchIdx = $i * 2;
            if (isset($batches[$batchIdx])) {
                DB::table('adjustment_items')->insert([
                    'adjustment_id' => $adjId,
                    'medicine_id' => $medicines[$i % count($medicines)],
                    'batch_id' => $batches[$batchIdx],
                    'quantity' => $type === 'count_adjustment' ? rand(-10, 10) : -rand(1, 20),
                    'reason' => $adjReasons[$i % 5],
                    'created_at' => $adjDate->format('Y-m-d H:i:s'),
                ]);
            }
        }

        // ── Narcotics Register ─────────────────────────────────────
        for ($i = 0; $i < 10; $i++) {
            $entryDate = Carbon::now()->subDays(rand(1, 30));
            $medIdx = $i % count($medicines);

            DB::table('narcotics_register')->insert([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'sale_id' => $sales[$i % count($sales)],
                'medicine_id' => $medicines[$medIdx],
                'batch_id' => $batches[$medIdx * 2] ?? $batches[0],
                'patient_name' => $customerData[$i % count($customerData)]['name'],
                'patient_address' => 'Kathmandu, Nepal',
                'doctor_name' => $doctorNames[$i],
                'prescription_number' => 'NRX-' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'quantity' => rand(1, 10),
                'balance' => rand(50, 200),
                'dispensed_by' => $this->userId,
                'created_at' => $entryDate->format('Y-m-d H:i:s'),
            ]);
        }

        // ── Substitutes ────────────────────────────────────────────
        // Create substitute mappings between medicines in the same category
        $substitutePairs = [
            [0, 7],   // Paracetamol ↔ Ibuprofen (both analgesics)
            [1, 6],   // Amoxicillin ↔ Azithromycin (both antibiotics)
            [1, 10],  // Amoxicillin ↔ Ciprofloxacin (both antibiotics)
            [2, 3],   // Metformin ↔ Amlodipine (different but often co-prescribed)
            [3, 8],   // Amlodipine ↔ Losartan (both cardiovascular)
            [4, 9],   // Omeprazole ↔ Pantoprazole (both PPIs)
            [5, 5],   // Cetirizine self (no-op, skip)
            [6, 10],  // Azithromycin ↔ Ciprofloxacin
            [7, 0],   // Ibuprofen ↔ Paracetamol
            [8, 3],   // Losartan ↔ Amlodipine
            [9, 4],   // Pantoprazole ↔ Omeprazole
            [10, 1],  // Ciprofloxacin ↔ Amoxicillin
        ];

        foreach ($substitutePairs as $pair) {
            if ($pair[0] === $pair[1]) continue;
            if (!isset($medicines[$pair[0]]) || !isset($medicines[$pair[1]])) continue;

            DB::table('substitutes')->insertOrIgnore([
                'medicine_id_1' => $medicines[$pair[0]],
                'medicine_id_2' => $medicines[$pair[1]],
                'notes' => 'Generic substitute',
                'created_at' => $now,
            ]);
        }

        // ── Audit Logs ─────────────────────────────────────────────
        $actions = ['created', 'updated', 'deleted', 'login', 'logout', 'sale', 'purchase', 'return', 'adjustment', 'dispense'];
        $models = ['Medicine', 'Customer', 'Supplier', 'Sale', 'Purchase', 'Prescription', 'User', 'InventoryAdjustment'];

        for ($i = 0; $i < 12; $i++) {
            DB::table('audit_logs')->insert([
                'company_id' => $companyId,
                'user_id' => $this->userId,
                'action' => $actions[$i % count($actions)],
                'model_type' => 'App\\Models\\' . $models[$i % count($models)],
                'model_id' => rand(1, 12),
                'old_values' => null,
                'new_values' => json_encode(['field' => 'value']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
