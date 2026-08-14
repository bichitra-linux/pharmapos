<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Medicine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class MedicineTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    public function test_can_list_medicines(): void
    {
        $data = $this->createFullTestData();
        $this->createMedicine($data['company'], ['brand_name' => 'Dolo 650']);
        $this->createMedicine($data['company'], ['brand_name' => 'Crocin']);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/medicines');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'brand_name', 'generic_name'],
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_can_create_a_medicine(): void
    {
        $data = $this->createFullTestData();
        $category = $this->createCategory($data['company']);
        $manufacturer = $this->createManufacturer($data['company']);

        $response = $this->actingAs($data['user'])
            ->postJson('/api/medicines', [
                'brand_name' => 'Paracetamol 500',
                'generic_name' => 'Paracetamol',
                'medicine_category_id' => $category->id,
                'manufacturer_id' => $manufacturer->id,
                'dosage_form' => 'tablet',
                'strength' => '500mg',
                'unit_type' => 'strip',
                'units_per_pack' => 10,
                'schedule_type' => 'otc',
                'is_prescription_required' => false,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Medicine created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'brand_name',
                    'generic_name',
                    'company_id',
                ],
            ]);

        $this->assertDatabaseHas('medicines', [
            'brand_name' => 'Paracetamol 500',
            'generic_name' => 'Paracetamol',
            'company_id' => $data['company']->id,
            'schedule_type' => 'otc',
            'is_active' => true,
        ]);
    }

    public function test_can_show_a_medicine(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company'], [
            'brand_name' => 'Show Test Med',
        ]);

        $response = $this->actingAs($data['user'])
            ->getJson("/api/medicines/{$medicine->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.brand_name', 'Show Test Med')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'brand_name',
                    'generic_name',
                    'batches',
                ],
            ]);
    }

    public function test_can_update_a_medicine(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company'], [
            'brand_name' => 'Old Name',
            'generic_name' => 'Old Generic',
        ]);

        $response = $this->actingAs($data['user'])
            ->putJson("/api/medicines/{$medicine->id}", [
                'brand_name' => 'New Name',
                'generic_name' => 'New Generic',
                'dosage_form' => 'capsule',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Medicine updated successfully.',
            ]);

        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'brand_name' => 'New Name',
            'generic_name' => 'New Generic',
        ]);
    }

    public function test_can_delete_a_medicine(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company']);

        $response = $this->actingAs($data['user'])
            ->deleteJson("/api/medicines/{$medicine->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Medicine deleted successfully.',
            ]);

        $this->assertSoftDeleted('medicines', ['id' => $medicine->id]);
    }

    public function test_cannot_see_medicines_from_another_company(): void
    {
        $companyA = $this->createCompany(['name' => 'Company A']);
        $outletA = $this->createOutlet($companyA);
        $userA = $this->createUser($companyA, $outletA);

        $companyB = $this->createCompany(['name' => 'Company B']);
        $outletB = $this->createOutlet($companyB);
        $userB = $this->createUser($companyB, $outletB);

        $this->createMedicine($companyA, ['brand_name' => 'Company A Med']);
        $this->createMedicine($companyB, ['brand_name' => 'Company B Med']);

        $response = $this->actingAs($userA)
            ->getJson('/api/medicines');

        $response->assertOk();

        $medicines = collect($response->json('data.data'));
        $this->assertTrue($medicines->contains('brand_name', 'Company A Med'));
        $this->assertFalse($medicines->contains('brand_name', 'Company B Med'));
    }

    public function test_search_by_brand_name_works(): void
    {
        $data = $this->createFullTestData();
        $this->createMedicine($data['company'], ['brand_name' => 'Dolo 650']);
        $this->createMedicine($data['company'], ['brand_name' => 'Crocin Advance']);
        $this->createMedicine($data['company'], ['brand_name' => 'Aspirin']);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/medicines?search=Dolo');

        $response->assertOk();

        $medicines = collect($response->json('data.data'));
        $this->assertCount(1, $medicines);
        $this->assertEquals('Dolo 650', $medicines->first()['brand_name']);
    }

    public function test_search_by_barcode_works(): void
    {
        $data = $this->createFullTestData();
        $this->createMedicine($data['company'], [
            'brand_name' => 'Barcode Med',
            'barcode' => '8901234567890',
        ]);
        $this->createMedicine($data['company'], [
            'brand_name' => 'Other Med',
            'barcode' => '8909999999999',
        ]);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/medicines?search=8901234567890');

        $response->assertOk();

        $medicines = collect($response->json('data.data'));
        $this->assertCount(1, $medicines);
        $this->assertEquals('Barcode Med', $medicines->first()['brand_name']);
    }

    public function test_search_endpoint_by_barcode(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company'], [
            'brand_name' => 'Scan Med',
            'barcode' => '8901234567890',
        ]);
        $this->createMedicineBatch($data['company'], $medicine, $data['outlet']);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/medicines/search?q=8901234567890');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $results = collect($response->json('data'));
        $this->assertCount(1, $results);
        $this->assertEquals('Scan Med', $results->first()['brand_name']);
    }

    public function test_search_endpoint_by_brand_name(): void
    {
        $data = $this->createFullTestData();
        $medicine = $this->createMedicine($data['company'], [
            'brand_name' => 'SearchTestMed',
        ]);
        $this->createMedicineBatch($data['company'], $medicine, $data['outlet']);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/medicines/search?q=Search');

        $response->assertOk();

        $results = collect($response->json('data'));
        $this->assertTrue($results->contains('brand_name', 'SearchTestMed'));
    }

    public function test_medicine_show_returns_404_for_other_company(): void
    {
        $companyA = $this->createCompany(['name' => 'Company A']);
        $outletA = $this->createOutlet($companyA);
        $userA = $this->createUser($companyA, $outletA);

        $companyB = $this->createCompany(['name' => 'Company B']);
        $medicineB = $this->createMedicine($companyB);

        $response = $this->actingAs($userA)
            ->getJson("/api/medicines/{$medicineB->id}");

        $response->assertStatus(404);
    }

    public function test_list_medicines_paginated(): void
    {
        $data = $this->createFullTestData();
        for ($i = 1; $i <= 30; $i++) {
            $this->createMedicine($data['company'], [
                'brand_name' => "Medicine {$i}",
            ]);
        }

        $response = $this->actingAs($data['user'])
            ->getJson('/api/medicines?per_page=10');

        $response->assertOk();

        $this->assertCount(10, $response->json('data.data'));
        $this->assertEquals(30, $response->json('data.total'));
    }
}
