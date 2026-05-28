<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class SaleTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    private function createMedicineWithStock(array $medicineOverrides = [], array $batchOverrides = []): array
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company'], $medicineOverrides);
        $batch = $this->createMedicineBatch(
            $data['company'],
            $medicine,
            $data['outlet'],
            array_merge([
                'quantity_in_stock' => 50,
                'selling_price_per_unit' => 20.00,
                'mrp_per_unit' => 25.00,
                'purchase_price_per_unit' => 15.00,
            ], $batchOverrides)
        );

        return array_merge($data, compact('medicine', 'batch'));
    }

    private function buildSalePayload(Medicine $medicine, MedicineBatch $batch, int $paymentMethodId, array $overrides = []): array
    {
        $defaults = [
            'items' => [
                [
                    'medicine_id' => $medicine->id,
                    'batch_id' => $batch->id,
                    'quantity' => 2,
                    'unit_price' => 20.00,
                    'discount' => 0,
                ],
            ],
            'payment_method_id' => $paymentMethodId,
            'paid_amount' => 45.20,
        ];

        return array_merge($defaults, $overrides);
    }

    public function test_can_create_a_sale_with_items(): void
    {
        $testData = $this->createMedicineWithStock();
        $payload = $this->buildSalePayload(
            $testData['medicine'],
            $testData['batch'],
            $testData['paymentMethod']->id
        );

        $response = $this->actingAs($testData['user'])
            ->postJson('/api/sales', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Sale completed successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'sale' => [
                        'id',
                        'invoice_number',
                        'subtotal',
                        'vat_amount',
                        'total_amount',
                        'items',
                    ],
                    'invoice_number',
                ],
            ]);

        $this->assertDatabaseHas('sales', [
            'company_id' => $testData['company']->id,
            'outlet_id' => $testData['outlet']->id,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'medicine_id' => $testData['medicine']->id,
            'batch_id' => $testData['batch']->id,
        ]);

        $this->assertDatabaseCount('sale_items', 1);
    }

    public function test_stock_is_deducted_after_sale(): void
    {
        $testData = $this->createMedicineWithStock();
        $initialStock = 50;
        $saleQuantity = 5;

        $this->actingAs($testData['user'])
            ->postJson('/api/sales', $this->buildSalePayload(
                $testData['medicine'],
                $testData['batch'],
                $testData['paymentMethod']->id,
                [
                    'items' => [
                        [
                            'medicine_id' => $testData['medicine']->id,
                            'batch_id' => $testData['batch']->id,
                            'quantity' => $saleQuantity,
                            'unit_price' => 20.00,
                        ],
                    ],
                    'paid_amount' => 20 * $saleQuantity * 1.13,
                ]
            ));

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $testData['batch']->id,
            'quantity_in_stock' => $initialStock - $saleQuantity,
        ]);
    }

    public function test_cannot_sell_more_than_available_stock(): void
    {
        $testData = $this->createMedicineWithStock([], [
            'quantity_in_stock' => 3,
        ]);

        $response = $this->actingAs($testData['user'])
            ->postJson('/api/sales', $this->buildSalePayload(
                $testData['medicine'],
                $testData['batch'],
                $testData['paymentMethod']->id,
                [
                    'items' => [
                        [
                            'medicine_id' => $testData['medicine']->id,
                            'batch_id' => $testData['batch']->id,
                            'quantity' => 10,
                            'unit_price' => 20.00,
                        ],
                    ],
                ]
            ));

        $response->assertStatus(500);

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $testData['batch']->id,
            'quantity_in_stock' => 3,
        ]);
    }

    public function test_vat_is_calculated_correctly_at_13_percent(): void
    {
        $testData = $this->createMedicineWithStock();
        $unitPrice = 100.00;
        $quantity = 2;
        $expectedSubtotal = $unitPrice * $quantity;
        $expectedVat = round($expectedSubtotal * 0.13, 2);
        $expectedTotal = $expectedSubtotal + $expectedVat;

        $response = $this->actingAs($testData['user'])
            ->postJson('/api/sales', $this->buildSalePayload(
                $testData['medicine'],
                $testData['batch'],
                $testData['paymentMethod']->id,
                [
                    'items' => [
                        [
                            'medicine_id' => $testData['medicine']->id,
                            'batch_id' => $testData['batch']->id,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'discount' => 0,
                        ],
                    ],
                    'paid_amount' => $expectedTotal,
                ]
            ));

        $response->assertStatus(201);

        $sale = $response->json('data.sale');
        $this->assertEquals($expectedSubtotal, (float) $sale['subtotal']);
        $this->assertEquals($expectedVat, (float) $sale['vat_amount']);
        $this->assertEquals($expectedTotal, (float) $sale['total_amount']);
        $this->assertEquals(13.0, (float) $sale['vat_percentage']);
    }

    public function test_invoice_number_is_generated(): void
    {
        $testData = $this->createMedicineWithStock();

        $response = $this->actingAs($testData['user'])
            ->postJson('/api/sales', $this->buildSalePayload(
                $testData['medicine'],
                $testData['batch'],
                $testData['paymentMethod']->id
            ));

        $response->assertStatus(201);

        $invoiceNumber = $response->json('data.invoice_number');
        $this->assertNotEmpty($invoiceNumber);
        $this->assertStringStartsWith('INV-', $invoiceNumber);

        $this->assertDatabaseHas('sales', [
            'invoice_number' => $invoiceNumber,
        ]);
    }

    public function test_schedule_h_medicine_requires_prescription(): void
    {
        $testData = $this->createMedicineWithStock([
            'schedule_type' => 'h',
            'is_prescription_required' => true,
        ]);

        $response = $this->actingAs($testData['user'])
            ->postJson('/api/sales', [
                'items' => [
                    [
                        'medicine_id' => $testData['medicine']->id,
                        'batch_id' => $testData['batch']->id,
                        'quantity' => 1,
                        'unit_price' => 20.00,
                    ],
                ],
                'payment_method_id' => $testData['paymentMethod']->id,
                'paid_amount' => 22.60,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('sales', [
            'company_id' => $testData['company']->id,
        ]);
    }

    public function test_schedule_h_medicine_with_prescription_succeeds(): void
    {
        $testData = $this->createMedicineWithStock([
            'schedule_type' => 'h',
            'is_prescription_required' => true,
        ]);

        $prescriptionId = DB::table('prescriptions')->insertGetId([
            'company_id' => $testData['company']->id,
            'customer_id' => $testData['customer']->id,
            'prescription_number' => 'RX-001',
            'doctor_name' => 'Dr. Smith',
            'prescription_date' => now(),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($testData['user'])
            ->postJson('/api/sales', [
                'items' => [
                    [
                        'medicine_id' => $testData['medicine']->id,
                        'batch_id' => $testData['batch']->id,
                        'quantity' => 1,
                        'unit_price' => 20.00,
                    ],
                ],
                'payment_method_id' => $testData['paymentMethod']->id,
                'paid_amount' => 22.60,
                'prescription_id' => $prescriptionId,
            ]);

        $response->assertStatus(201);
    }

    public function test_can_list_sales(): void
    {
        $testData = $this->createMedicineWithStock();

        $this->actingAs($testData['user'])
            ->postJson('/api/sales', $this->buildSalePayload(
                $testData['medicine'],
                $testData['batch'],
                $testData['paymentMethod']->id
            ));

        $this->actingAs($testData['user'])
            ->postJson('/api/sales', $this->buildSalePayload(
                $testData['medicine'],
                $testData['batch'],
                $testData['paymentMethod']->id
            ));

        $response = $this->actingAs($testData['user'])
            ->getJson('/api/sales');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'invoice_number',
                            'total_amount',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_can_show_a_sale_with_items(): void
    {
        $testData = $this->createMedicineWithStock();

        $createResponse = $this->actingAs($testData['user'])
            ->postJson('/api/sales', $this->buildSalePayload(
                $testData['medicine'],
                $testData['batch'],
                $testData['paymentMethod']->id
            ));

        $saleId = $createResponse->json('data.sale.id');

        $response = $this->actingAs($testData['user'])
            ->getJson("/api/sales/{$saleId}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'invoice_number',
                    'items' => [
                        '*' => [
                            'id',
                            'medicine_id',
                            'batch_id',
                            'quantity',
                            'selling_price',
                            'total',
                        ],
                    ],
                    'payments',
                ],
            ]);
    }

    public function test_sale_with_customer_updates_loyalty_points(): void
    {
        $testData = $this->createMedicineWithStock();
        $totalAmount = 45.20;

        $this->actingAs($testData['user'])
            ->postJson('/api/sales', $this->buildSalePayload(
                $testData['medicine'],
                $testData['batch'],
                $testData['paymentMethod']->id,
                [
                    'customer_id' => $testData['customer']->id,
                    'paid_amount' => $totalAmount,
                ]
            ));

        $expectedPoints = (int) floor($totalAmount / 100);

        $this->assertDatabaseHas('customers', [
            'id' => $testData['customer']->id,
            'loyalty_points' => $expectedPoints,
        ]);
    }

    public function test_sale_with_partial_payment_creates_due(): void
    {
        $testData = $this->createMedicineWithStock();

        $response = $this->actingAs($testData['user'])
            ->postJson('/api/sales', $this->buildSalePayload(
                $testData['medicine'],
                $testData['batch'],
                $testData['paymentMethod']->id,
                [
                    'paid_amount' => 10.00,
                ]
            ));

        $response->assertStatus(201);

        $sale = $response->json('data.sale');
        $this->assertEqualsWithDelta(10.00, (float) $sale['paid_amount'], 0.01);
        $this->assertGreaterThan(0, (float) $sale['due_amount']);
        $this->assertEquals('partial', $sale['payment_status']);
    }

    public function test_multiple_items_in_single_sale(): void
    {
        $testData = $this->createMedicineWithStock();
        $medicine2 = $this->createMedicine($testData['company'], [
            'brand_name' => 'Second Med',
        ]);
        $batch2 = $this->createMedicineBatch(
            $testData['company'],
            $medicine2,
            $testData['outlet'],
            [
                'quantity_in_stock' => 30,
                'selling_price_per_unit' => 15.00,
            ]
        );

        $response = $this->actingAs($testData['user'])
            ->postJson('/api/sales', [
                'items' => [
                    [
                        'medicine_id' => $testData['medicine']->id,
                        'batch_id' => $testData['batch']->id,
                        'quantity' => 2,
                        'unit_price' => 20.00,
                    ],
                    [
                        'medicine_id' => $medicine2->id,
                        'batch_id' => $batch2->id,
                        'quantity' => 3,
                        'unit_price' => 15.00,
                    ],
                ],
                'payment_method_id' => $testData['paymentMethod']->id,
                'paid_amount' => (2 * 20 + 3 * 15) * 1.13,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('sale_items', 2);
    }
}
