<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class PurchaseTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    private function createPurchasePayload(int $supplierId, int $medicineId, array $overrides = []): array
    {
        $defaults = [
            'supplier_id' => $supplierId,
            'items' => [
                [
                    'medicine_id' => $medicineId,
                    'batch_number' => 'BATCH-001',
                    'expiry_date' => now()->addYear()->format('Y-m-d'),
                    'quantity' => 100,
                    'purchase_price' => 10.00,
                    'mrp' => 15.00,
                    'selling_price' => 13.00,
                ],
            ],
            'paid_amount' => 0,
        ];

        return array_merge($defaults, $overrides);
    }

    public function test_can_create_a_purchase(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);

        $response = $this->actingAs($data['user'])
            ->postJson('/api/purchases', $this->createPurchasePayload(
                $data['supplier']->id,
                $medicine->id
            ));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase order created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'purchase_number',
                    'status',
                    'items',
                ],
            ]);

        $this->assertDatabaseHas('purchases', [
            'company_id' => $data['company']->id,
            'supplier_id' => $data['supplier']->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('purchase_items', [
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 100,
        ]);
    }

    public function test_can_receive_a_purchase_grn(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);

        $createResponse = $this->actingAs($data['user'])
            ->postJson('/api/purchases', $this->createPurchasePayload(
                $data['supplier']->id,
                $medicine->id
            ));

        $purchaseId = $createResponse->json('data.id');
        $purchaseItemId = DB::table('purchase_items')
            ->where('purchase_id', $purchaseId)
            ->first()->id;

        $response = $this->actingAs($data['user'])
            ->postJson("/api/purchases/{$purchaseId}/receive", [
                'items' => [
                    [
                        'purchase_item_id' => $purchaseItemId,
                        'received_quantity' => 100,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchaseId,
            'status' => 'received',
        ]);
    }

    public function test_stock_increases_after_grn(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch(
            $data['company'],
            $medicine,
            $data['outlet'],
            [
                'batch_number' => 'EXISTING-BATCH',
                'quantity_in_stock' => 50,
            ]
        );

        $createResponse = $this->actingAs($data['user'])
            ->postJson('/api/purchases', $this->createPurchasePayload(
                $data['supplier']->id,
                $medicine->id,
                [
                    'items' => [
                        [
                            'medicine_id' => $medicine->id,
                            'batch_number' => 'EXISTING-BATCH',
                            'expiry_date' => now()->addYear()->format('Y-m-d'),
                            'quantity' => 100,
                            'purchase_price' => 10.00,
                            'mrp' => 15.00,
                            'selling_price' => 13.00,
                        ],
                    ],
                ]
            ));

        $purchaseId = $createResponse->json('data.id');
        $purchaseItemId = DB::table('purchase_items')
            ->where('purchase_id', $purchaseId)
            ->first()->id;

        $this->actingAs($data['user'])
            ->postJson("/api/purchases/{$purchaseId}/receive", [
                'items' => [
                    [
                        'purchase_item_id' => $purchaseItemId,
                        'received_quantity' => 100,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batch->id,
            'quantity_in_stock' => 150,
        ]);
    }

    public function test_cannot_receive_expired_batch(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);

        $purchase = \App\Models\Purchase::create([
            'company_id' => $data['company']->id,
            'outlet_id' => $data['outlet']->id,
            'supplier_id' => $data['supplier']->id,
            'purchase_number' => 'PO-EXPIRED-001',
            'purchase_date' => now(),
            'subtotal' => 1000,
            'vat' => 130,
            'discount' => 0,
            'total' => 1130,
            'paid_amount' => 0,
            'due_amount' => 1130,
            'payment_status' => 'due',
            'status' => 'draft',
        ]);

        $purchaseItemId = DB::table('purchase_items')->insertGetId([
            'purchase_id' => $purchase->id,
            'medicine_id' => $medicine->id,
            'batch_number' => 'EXPIRED-BATCH',
            'expiry_date' => now()->subMonth()->format('Y-m-d'),
            'quantity' => 100,
            'purchase_price' => 10.00,
            'mrp' => 15.00,
            'selling_price' => 13.00,
            'total' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($data['user'])
            ->postJson("/api/purchases/{$purchase->id}/receive", [
                'items' => [
                    [
                        'purchase_item_id' => $purchaseItemId,
                        'received_quantity' => 100,
                    ],
                ],
            ]);

        $response->assertStatus(500);
    }

    public function test_can_list_purchases(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);

        $this->actingAs($data['user'])
            ->postJson('/api/purchases', $this->createPurchasePayload(
                $data['supplier']->id,
                $medicine->id
            ));

        $this->actingAs($data['user'])
            ->postJson('/api/purchases', $this->createPurchasePayload(
                $data['supplier']->id,
                $medicine->id
            ));

        $response = $this->actingAs($data['user'])
            ->getJson('/api/purchases');

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
                            'purchase_number',
                            'supplier',
                            'status',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_purchase_number_is_generated(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);

        $response = $this->actingAs($data['user'])
            ->postJson('/api/purchases', $this->createPurchasePayload(
                $data['supplier']->id,
                $medicine->id
            ));

        $response->assertStatus(201);

        $purchaseNumber = $response->json('data.purchase_number');
        $this->assertNotEmpty($purchaseNumber);
        $this->assertStringStartsWith('PO-', $purchaseNumber);
    }

    public function test_can_show_a_purchase(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);

        $createResponse = $this->actingAs($data['user'])
            ->postJson('/api/purchases', $this->createPurchasePayload(
                $data['supplier']->id,
                $medicine->id
            ));

        $purchaseId = $createResponse->json('data.id');

        $response = $this->actingAs($data['user'])
            ->getJson("/api/purchases/{$purchaseId}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'purchase_number',
                    'supplier',
                    'items',
                ],
            ]);
    }

    public function test_grn_creates_new_batch_if_not_exists(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);

        $createResponse = $this->actingAs($data['user'])
            ->postJson('/api/purchases', $this->createPurchasePayload(
                $data['supplier']->id,
                $medicine->id,
                [
                    'items' => [
                        [
                            'medicine_id' => $medicine->id,
                            'batch_number' => 'NEW-BATCH-001',
                            'expiry_date' => now()->addYear()->format('Y-m-d'),
                            'quantity' => 50,
                            'purchase_price' => 12.00,
                            'mrp' => 18.00,
                            'selling_price' => 15.00,
                        ],
                    ],
                ]
            ));

        $purchaseId = $createResponse->json('data.id');
        $purchaseItemId = DB::table('purchase_items')
            ->where('purchase_id', $purchaseId)
            ->first()->id;

        $this->actingAs($data['user'])
            ->postJson("/api/purchases/{$purchaseId}/receive", [
                'items' => [
                    [
                        'purchase_item_id' => $purchaseItemId,
                        'received_quantity' => 50,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('medicine_batches', [
            'medicine_id' => $medicine->id,
            'outlet_id' => $data['outlet']->id,
            'batch_number' => 'NEW-BATCH-001',
            'quantity_in_stock' => 50,
            'purchase_price_per_unit' => 12.00,
            'mrp_per_unit' => 18.00,
            'selling_price_per_unit' => 15.00,
        ]);
    }
}
