<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CustomerReturn;
use App\Models\InventoryAdjustment;
use App\Models\Prescription;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Tests\Feature\Concerns\CreatesTestData;

class PolicyTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    private User $owner;
    private User $otherOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $this->owner = $this->createUser($company, $outlet, ['role' => 'owner']);

        $otherCompany = $this->createCompany(['name' => 'Other Pharmacy', 'slug' => 'other-']);
        $otherOutlet = $this->createOutlet($otherCompany);
        $this->otherOwner = $this->createUser($otherCompany, $otherOutlet, [
            'role' => 'owner',
            'email' => 'other@test.com',
        ]);
    }

    private function createSale(int $companyId): Sale
    {
        return Sale::create([
            'company_id' => $companyId,
            'outlet_id' => 1,
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'subtotal' => 100,
            'total_amount' => 113,
            'paid_amount' => 113,
            'due_amount' => 0,
            'payment_status' => 'paid',
            'sale_type' => 'walk_in',
        ]);
    }

    public function test_sale_policy_allows_own_company(): void
    {
        $sale = $this->createSale($this->owner->company_id);

        $this->assertTrue(Gate::forUser($this->owner)->allows('view', $sale));
        $this->assertFalse(Gate::forUser($this->otherOwner)->allows('view', $sale));
    }

    public function test_prescription_policy_allows_own_company(): void
    {
        $rx = Prescription::create([
            'company_id' => $this->owner->company_id,
            'prescription_number' => 'RX-TEST-'.uniqid(),
            'status' => 'pending',
        ]);

        $this->assertTrue(Gate::forUser($this->owner)->allows('view', $rx));
        $this->assertFalse(Gate::forUser($this->otherOwner)->allows('view', $rx));
    }

    public function test_customer_return_policy_allows_own_company(): void
    {
        $sale = $this->createSale($this->owner->company_id);
        $return = CustomerReturn::create([
            'company_id' => $this->owner->company_id,
            'outlet_id' => 1,
            'sale_id' => $sale->id,
            'return_number' => 'RET-TEST-'.uniqid(),
            'total_amount' => 100,
            'refund_amount' => 100,
            'return_date' => now(),
        ]);

        $this->assertTrue(Gate::forUser($this->owner)->allows('view', $return));
        $this->assertFalse(Gate::forUser($this->otherOwner)->allows('view', $return));
    }

    public function test_inventory_adjustment_policy_allows_own_company(): void
    {
        $adjustment = InventoryAdjustment::create([
            'company_id' => $this->owner->company_id,
            'outlet_id' => 1,
            'type' => 'count_adjustment',
            'adjusted_by' => $this->owner->id,
        ]);

        $this->assertTrue(Gate::forUser($this->owner)->allows('view', $adjustment));
        $this->assertFalse(Gate::forUser($this->otherOwner)->allows('view', $adjustment));
    }

    public function test_user_policy_allows_own_company(): void
    {
        $target = User::create([
            'company_id' => $this->owner->company_id,
            'outlet_id' => 1,
            'name' => 'Target',
            'email' => 'target@test.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
        ]);

        $this->assertTrue(Gate::forUser($this->owner)->allows('view', $target));
        $this->assertFalse(Gate::forUser($this->otherOwner)->allows('view', $target));
    }

    public function test_create_sale_requires_permission(): void
    {
        $this->assertTrue(Gate::forUser($this->owner)->allows('create', Sale::class));
    }

    public function test_sale_refund_action_abides_by_policy(): void
    {
        $sale = $this->createSale($this->owner->company_id);

        $this->assertTrue(Gate::forUser($this->owner)->allows('refund', $sale));
    }
}
