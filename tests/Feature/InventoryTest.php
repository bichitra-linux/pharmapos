<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class InventoryTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    public function test_can_view_stock_report(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company'], ['brand_name' => 'Stock Med']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 100,
            'purchase_price_per_unit' => 10.00,
        ]);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/inventory/stock');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'batch_number',
                        'quantity_in_stock',
                        'expiry_date',
                    ],
                ],
            ]);

        $stockData = collect($response->json('data'));
        $stockMed = $stockData->firstWhere('batch_number', $batch->batch_number);
        $this->assertNotNull($stockMed);
        $this->assertEquals(100, (float) $stockMed['quantity_in_stock']);
    }

    private function adjustmentPayload(int $batchId, int $medicineId, string $type, float $quantity, string $reason): array
    {
        return [
            'type' => $type,
            'reason' => $reason,
            'items' => [
                ['medicine_id' => $medicineId, 'batch_id' => $batchId, 'quantity' => $quantity],
            ],
        ];
    }

    public function test_can_create_stock_adjustment(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 100,
        ]);

        $response = $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', $this->adjustmentPayload(
                $batch->id, $medicine->id, 'damage', 10, 'Damaged during transport'
            ));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Stock adjustment recorded successfully.',
            ]);

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batch->id,
            'quantity_in_stock' => 90,
        ]);

        $this->assertDatabaseHas('inventory_adjustments', [
            'company_id' => $data['company']->id,
            'outlet_id' => $data['outlet']->id,
            'type' => 'damage',
            'reason' => 'Damaged during transport',
        ]);
    }

    public function test_stock_decreases_on_damage_adjustment(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 50,
        ]);

        $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', $this->adjustmentPayload(
                $batch->id, $medicine->id, 'damage', 20, 'Broken bottles'
            ));

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batch->id,
            'quantity_in_stock' => 30,
        ]);
    }

    public function test_expiry_adjustment_decreases_stock(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 25,
        ]);

        $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', $this->adjustmentPayload(
                $batch->id, $medicine->id, 'expiry', 15, 'Expired stock removal'
            ));

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batch->id,
            'quantity_in_stock' => 10,
        ]);
    }

    public function test_cannot_adjust_to_negative_stock(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 5,
        ]);

        $response = $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', $this->adjustmentPayload(
                $batch->id, $medicine->id, 'damage', 10, 'Trying to remove more than available'
            ));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString(
            'Adjustment would result in negative stock for batch',
            $response->json('message')
        );

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batch->id,
            'quantity_in_stock' => 5,
        ]);
    }

    public function test_count_adjustment_sets_exact_quantity(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 100,
        ]);

        $response = $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', $this->adjustmentPayload(
                $batch->id, $medicine->id, 'count_adjustment', 85, 'Physical count mismatch'
            ));

        $response->assertStatus(201);

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batch->id,
            'quantity_in_stock' => 85,
        ]);
    }

    public function test_can_list_adjustments(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 100,
        ]);

        $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', $this->adjustmentPayload(
                $batch->id, $medicine->id, 'damage', 5, 'First damage'
            ));

        $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', $this->adjustmentPayload(
                $batch->id, $medicine->id, 'expiry', 10, 'Expired stock'
            ));

        $response = $this->actingAs($data['user'])
            ->getJson('/api/inventory/adjustments');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_stock_report_is_empty_when_no_batches_exist(): void
    {
        $data = $this->createFullTestData();
        $this->createMedicine($data['company'], ['brand_name' => 'No Stock Med']);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/inventory/stock');

        $response->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    public function test_batch_deactivates_when_stock_reaches_zero(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 5,
        ]);

        $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', $this->adjustmentPayload(
                $batch->id, $medicine->id, 'damage', 5, 'Complete loss'
            ));

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batch->id,
            'quantity_in_stock' => 0,
            'is_active' => false,
        ]);
    }
}
