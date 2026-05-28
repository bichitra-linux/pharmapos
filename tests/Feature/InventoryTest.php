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
        $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
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
                    'data' => [
                        '*' => [
                            'id',
                            'brand_name',
                            'current_stock',
                            'stock_value',
                        ],
                    ],
                ],
            ]);

        $stockData = collect($response->json('data.data'));
        $stockMed = $stockData->firstWhere('brand_name', 'Stock Med');
        $this->assertNotNull($stockMed);
        $this->assertEquals(100, (float) $stockMed['current_stock']);
    }

    public function test_can_create_stock_adjustment(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 100,
        ]);

        $response = $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', [
                'batch_id' => $batch->id,
                'type' => 'damage',
                'quantity' => 10,
                'reason' => 'Damaged during transport',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Stock adjustment recorded successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'previous_quantity',
                    'new_quantity',
                ],
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
            ->postJson('/api/inventory/adjustments', [
                'batch_id' => $batch->id,
                'type' => 'damage',
                'quantity' => 20,
                'reason' => 'Broken bottles',
            ]);

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
            ->postJson('/api/inventory/adjustments', [
                'batch_id' => $batch->id,
                'type' => 'expiry',
                'quantity' => 15,
                'reason' => 'Expired stock removal',
            ]);

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
            ->postJson('/api/inventory/adjustments', [
                'batch_id' => $batch->id,
                'type' => 'damage',
                'quantity' => 10,
                'reason' => 'Trying to remove more than available',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Adjustment would result in negative stock.',
            ]);

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
            ->postJson('/api/inventory/adjustments', [
                'batch_id' => $batch->id,
                'type' => 'count_adjustment',
                'quantity' => 85,
                'reason' => 'Physical count mismatch',
            ]);

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
            ->postJson('/api/inventory/adjustments', [
                'batch_id' => $batch->id,
                'type' => 'damage',
                'quantity' => 5,
                'reason' => 'First damage',
            ]);

        $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', [
                'batch_id' => $batch->id,
                'type' => 'expiry',
                'quantity' => 10,
                'reason' => 'Expired stock',
            ]);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/inventory/adjustments');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_stock_report_shows_zero_for_no_batches(): void
    {
        $data = $this->createFullTestData();
        $this->createMedicine($data['company'], ['brand_name' => 'No Stock Med']);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/inventory/stock');

        $response->assertOk();

        $stockData = collect($response->json('data.data'));
        $noStockMed = $stockData->firstWhere('brand_name', 'No Stock Med');
        $this->assertNotNull($noStockMed);
        $this->assertEquals(0, (float) $noStockMed['current_stock']);
    }

    public function test_batch_deactivates_when_stock_reaches_zero(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 5,
        ]);

        $this->actingAs($data['user'])
            ->postJson('/api/inventory/adjustments', [
                'batch_id' => $batch->id,
                'type' => 'damage',
                'quantity' => 5,
                'reason' => 'Complete loss',
            ]);

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batch->id,
            'quantity_in_stock' => 0,
            'is_active' => false,
        ]);
    }
}
