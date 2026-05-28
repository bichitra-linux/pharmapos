<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class CustomerTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    public function test_can_create_a_customer(): void
    {
        $data = $this->createFullTestData();

        $response = $this->actingAs($data['user'])
            ->postJson('/api/customers', [
                'name' => 'New Customer',
                'phone' => '9841555555',
                'email' => 'new@customer.com',
                'address' => 'Kathmandu',
                'gender' => 'male',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Customer created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'phone',
                    'email',
                    'company_id',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'New Customer',
            'phone' => '9841555555',
            'email' => 'new@customer.com',
            'company_id' => $data['company']->id,
            'is_active' => true,
        ]);
    }

    public function test_can_list_customers(): void
    {
        $data = $this->createFullTestData();

        $response = $this->actingAs($data['user'])
            ->getJson('/api/customers');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'phone'],
                    ],
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data.data')));
    }

    public function test_customers_are_scoped_to_company(): void
    {
        $companyA = $this->createCompany(['name' => 'Company A']);
        $outletA = $this->createOutlet($companyA);
        $userA = $this->createUser($companyA, $outletA);

        $companyB = $this->createCompany(['name' => 'Company B']);
        $outletB = $this->createOutlet($companyB);
        $userB = $this->createUser($companyB, $outletB);

        $this->createCustomer($companyA, ['name' => 'Customer A']);
        $this->createCustomer($companyB, ['name' => 'Customer B']);

        $response = $this->actingAs($userA)
            ->getJson('/api/customers');

        $response->assertOk();

        $customers = collect($response->json('data.data'));
        $this->assertTrue($customers->contains('name', 'Customer A'));
        $this->assertFalse($customers->contains('name', 'Customer B'));
    }

    public function test_can_update_a_customer(): void
    {
        $data = $this->createFullTestData();
        $customer = $this->createCustomer($data['company'], [
            'name' => 'Old Name',
            'phone' => '9841000000',
        ]);

        $response = $this->actingAs($data['user'])
            ->putJson("/api/customers/{$customer->id}", [
                'name' => 'Updated Name',
                'phone' => '9841999999',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Customer updated successfully.',
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'phone' => '9841999999',
        ]);
    }

    public function test_can_get_customer_history(): void
    {
        $data = $this->createFullTestData();
        $customer = $this->createCustomer($data['company']);
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet']);

        $this->actingAs($data['user'])
            ->postJson('/api/sales', [
                'items' => [
                    [
                        'medicine_id' => $medicine->id,
                        'batch_id' => $batch->id,
                        'quantity' => 1,
                        'unit_price' => 20.00,
                    ],
                ],
                'customer_id' => $customer->id,
                'payment_method_id' => $data['paymentMethod']->id,
                'paid_amount' => 22.60,
            ]);

        $response = $this->actingAs($data['user'])
            ->getJson("/api/customers/{$customer->id}/history");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'customer' => ['id', 'name'],
                    'sales' => [
                        'data' => [
                            '*' => [
                                'id',
                                'invoice_number',
                            ],
                        ],
                    ],
                    'prescriptions',
                ],
            ]);

        $this->assertCount(1, $response->json('data.sales.data'));
    }

    public function test_customer_history_returns_404_for_other_company(): void
    {
        $companyA = $this->createCompany(['name' => 'Company A']);
        $outletA = $this->createOutlet($companyA);
        $userA = $this->createUser($companyA, $outletA);

        $companyB = $this->createCompany(['name' => 'Company B']);
        $customerB = $this->createCustomer($companyB);

        $response = $this->actingAs($userA)
            ->getJson("/api/customers/{$customerB->id}/history");

        $response->assertStatus(404);
    }

    public function test_can_search_customers(): void
    {
        $data = $this->createFullTestData();
        $this->createCustomer($data['company'], [
            'name' => 'UniqueSearchName',
            'phone' => '9841111111',
        ]);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/customers?search=UniqueSearchName');

        $response->assertOk();

        $customers = collect($response->json('data.data'));
        $this->assertCount(1, $customers);
        $this->assertEquals('UniqueSearchName', $customers->first()['name']);
    }

    public function test_can_search_customers_by_phone(): void
    {
        $data = $this->createFullTestData();
        $this->createCustomer($data['company'], [
            'name' => 'Phone Test',
            'phone' => '9841333333',
        ]);
        $this->createCustomer($data['company'], [
            'name' => 'Other',
            'phone' => '9841444444',
        ]);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/customers?search=9841333333');

        $response->assertOk();

        $customers = collect($response->json('data.data'));
        $this->assertCount(1, $customers);
        $this->assertEquals('Phone Test', $customers->first()['name']);
    }
}
