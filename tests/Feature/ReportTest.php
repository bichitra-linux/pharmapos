<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class ReportTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    private function createSaleForReport(array $data, float $unitPrice = 100.00, int $qty = 2): void
    {
        $medicine = $this->createMedicine($data['company']);
        $batch = $this->createMedicineBatch($data['company'], $medicine, $data['outlet'], [
            'quantity_in_stock' => 1000,
            'selling_price_per_unit' => $unitPrice,
            'purchase_price_per_unit' => $unitPrice * 0.7,
            'mrp_per_unit' => $unitPrice * 1.2,
        ]);

        $total = $unitPrice * $qty * 1.13;

        $this->actingAs($data['user'])
            ->postJson('/api/sales', [
                'items' => [
                    [
                        'medicine_id' => $medicine->id,
                        'batch_id' => $batch->id,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                    ],
                ],
                'payment_method_id' => $data['paymentMethod']->id,
                'paid_amount' => $total,
            ]);
    }

    public function test_sales_report_returns_data(): void
    {
        $data = $this->createFullTestData();
        $this->createSaleForReport($data, 100.00, 2);
        $this->createSaleForReport($data, 50.00, 3);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/reports/sales');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'date',
                        'count',
                        'subtotal',
                        'vat',
                        'discount',
                        'total',
                    ],
                ],
            ]);

        $reportData = collect($response->json('data'));
        $this->assertNotEmpty($reportData);
        $todayReport = $reportData->firstWhere('date', now()->format('Y-m-d'));
        $this->assertNotNull($todayReport);
        $this->assertEquals(2, $todayReport['count']);
    }

    public function test_sales_report_with_date_range(): void
    {
        $data = $this->createFullTestData();
        $this->createSaleForReport($data);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/reports/sales?date_from=' . now()->format('Y-m-d') . '&date_to=' . now()->format('Y-m-d'));

        $response->assertOk();

        $reportData = collect($response->json('data'));
        $this->assertNotEmpty($reportData);
    }

    public function test_vat_report_returns_correct_totals(): void
    {
        $data = $this->createFullTestData();
        $this->createSaleForReport($data, 100.00, 2);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/reports/vat');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'sales' => [
                        'taxable_amount',
                        'output_vat',
                    ],
                    'purchases' => [
                        'taxable_amount',
                        'input_vat',
                    ],
                    'net_vat_payable',
                ],
            ]);

        $vatData = $response->json('data');
        $this->assertGreaterThan(0, (float) $vatData['sales']['output_vat']);
        $this->assertEqualsWithDelta(
            (float) $vatData['sales']['taxable_amount'] * 0.13,
            (float) $vatData['sales']['output_vat'],
            0.10
        );
    }

    public function test_expiry_report_returns_expiring_medicines(): void
    {
        $this->markTestSkipped('Requires MySQL - uses CURDATE() and DATEDIFF() which are not available in SQLite.');
    }

    public function test_inventory_report_returns_summary(): void
    {
        $data = $this->createFullTestData();
        $medicine1 = $this->createMedicine($data['company'], ['brand_name' => 'Inv Med 1']);
        $medicine2 = $this->createMedicine($data['company'], ['brand_name' => 'Inv Med 2']);

        $this->createMedicineBatch($data['company'], $medicine1, $data['outlet'], [
            'quantity_in_stock' => 100,
            'purchase_price_per_unit' => 10.00,
        ]);
        $this->createMedicineBatch($data['company'], $medicine2, $data['outlet'], [
            'quantity_in_stock' => 200,
            'purchase_price_per_unit' => 5.00,
        ]);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/reports/inventory');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary' => [
                        'total_medicines',
                        'total_units',
                        'stock_at_cost',
                        'stock_at_mrp',
                    ],
                    'category_wise',
                ],
            ]);

        $summary = $response->json('data.summary');
        $this->assertEquals(2, (int) $summary['total_medicines']);
        $this->assertEquals(300, (float) $summary['total_units']);
    }

    public function test_profit_loss_report(): void
    {
        $data = $this->createFullTestData();
        $this->createSaleForReport($data, 100.00, 2);

        $response = $this->actingAs($data['user'])
            ->getJson('/api/reports/profit-loss');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'revenue',
                    'cost_of_goods',
                    'gross_profit',
                    'margin',
                    'vat_collected',
                    'discounts_given',
                ],
            ]);

        $plData = $response->json('data');
        $this->assertGreaterThan(0, (float) $plData['revenue']);
        $this->assertGreaterThan(0, (float) $plData['gross_profit']);
    }

    public function test_vat_report_with_date_range(): void
    {
        $data = $this->createFullTestData();
        $this->createSaleForReport($data, 200.00, 1);

        $dateFrom = now()->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $response = $this->actingAs($data['user'])
            ->getJson("/api/reports/vat?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertOk();

        $vatData = $response->json('data');
        $this->assertEquals($dateFrom, $vatData['period']['from']);
        $this->assertEquals($dateTo, $vatData['period']['to']);
        $this->assertGreaterThan(0, (float) $vatData['sales']['output_vat']);
    }

    public function test_reports_require_authentication(): void
    {
        $endpoints = [
            '/api/reports/sales',
            '/api/reports/purchases',
            '/api/reports/inventory',
            '/api/reports/expiry',
            '/api/reports/profit-loss',
            '/api/reports/vat',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(401);
        }
    }

    public function test_sales_report_is_scoped_to_company(): void
    {
        $companyA = $this->createCompany(['name' => 'Company A']);
        $outletA = $this->createOutlet($companyA);
        $userA = $this->createUser($companyA, $outletA);

        $companyB = $this->createCompany(['name' => 'Company B']);
        $outletB = $this->createOutlet($companyB);
        $userB = $this->createUser($companyB, $outletB);

        $paymentMethodA = $this->createPaymentMethod($companyA);
        $paymentMethodB = $this->createPaymentMethod($companyB);

        $dataA = [
            'company' => $companyA,
            'outlet' => $outletA,
            'user' => $userA,
            'paymentMethod' => $paymentMethodA,
            'customer' => $this->createCustomer($companyA),
        ];

        $dataB = [
            'company' => $companyB,
            'outlet' => $outletB,
            'user' => $userB,
            'paymentMethod' => $paymentMethodB,
            'customer' => $this->createCustomer($companyB),
        ];

        $this->createSaleForReport($dataA, 100.00, 1);
        $this->createSaleForReport($dataA, 50.00, 1);

        $this->createSaleForReport($dataB, 200.00, 1);
        $this->createSaleForReport($dataB, 150.00, 1);
        $this->createSaleForReport($dataB, 75.00, 1);

        $responseA = $this->actingAs($userA)
            ->getJson('/api/reports/sales');

        $responseA->assertOk();
        $reportDataA = collect($responseA->json('data'));
        $todayReportA = $reportDataA->firstWhere('date', now()->format('Y-m-d'));
        $this->assertEquals(2, $todayReportA['count']);

        $responseB = $this->actingAs($userB)
            ->getJson('/api/reports/sales');

        $responseB->assertOk();
        $reportDataB = collect($responseB->json('data'));
        $todayReportB = $reportDataB->firstWhere('date', now()->format('Y-m-d'));
        $this->assertEquals(3, $todayReportB['count']);
    }
}
