<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Manufacturer;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicineCategory;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SuperAdmin;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait CreatesTestData
{
    protected function createCompany(array $overrides = []): Company
    {
        $defaults = [
            'name' => 'Test Pharmacy',
            'slug' => 'test-pharmacy-' . Str::random(5),
            'email' => 'test@pharmacy.com',
            'phone' => '9841000000',
            'address' => 'Kathmandu, Nepal',
            'pan_number' => '123456789',
            'is_active' => true,
            'subscription_expires_at' => now()->addYear(),
        ];

        return Company::create(array_merge($defaults, $overrides));
    }

    protected function createOutlet(Company $company, array $overrides = []): Outlet
    {
        $defaults = [
            'company_id' => $company->id,
            'name' => 'Main Outlet',
            'address' => 'Kathmandu, Nepal',
            'phone' => '9841000000',
            'is_main_outlet' => true,
            'is_active' => true,
        ];

        return Outlet::create(array_merge($defaults, $overrides));
    }

    protected function createUser(Company $company, Outlet $outlet, array $overrides = []): User
    {
        $defaults = [
            'company_id' => $company->id,
            'outlet_id' => $outlet->id,
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => Hash::make('password123'),
            'phone' => '9841000001',
            'role' => 'owner',
            'is_active' => true,
        ];

        $user = User::create(array_merge($defaults, $overrides));

        DB::table('users')
            ->where('id', $user->id)
            ->update(['email_verified_at' => now()]);

        return $user->fresh();
    }

    protected function createSuperAdmin(array $overrides = []): SuperAdmin
    {
        $defaults = [
            'name' => 'Super Admin',
            'email' => 'admin@pharmapos.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ];

        return SuperAdmin::create(array_merge($defaults, $overrides));
    }

    protected function createMedicine(Company $company, array $overrides = []): Medicine
    {
        $defaults = [
            'company_id' => $company->id,
            'generic_name' => 'Paracetamol',
            'brand_name' => 'Dolo 650',
            'dosage_form' => 'tablet',
            'strength' => '650mg',
            'unit_type' => 'strip',
            'units_per_pack' => 10,
            'schedule_type' => 'otc',
            'is_prescription_required' => false,
            'is_active' => true,
        ];

        return Medicine::create(array_merge($defaults, $overrides));
    }

    protected function createMedicineBatch(
        Company $company,
        Medicine $medicine,
        Outlet $outlet,
        array $overrides = []
    ): MedicineBatch {
        $defaults = [
            'company_id' => $company->id,
            'medicine_id' => $medicine->id,
            'outlet_id' => $outlet->id,
            'batch_number' => 'BATCH-' . Str::random(6),
            'expiry_date' => now()->addYear(),
            'quantity_in_stock' => 100,
            'purchase_price_per_unit' => 10.00,
            'mrp_per_unit' => 15.00,
            'selling_price_per_unit' => 13.00,
            'is_active' => true,
        ];

        return MedicineBatch::create(array_merge($defaults, $overrides));
    }

    protected function createCustomer(Company $company, array $overrides = []): Customer
    {
        $defaults = [
            'company_id' => $company->id,
            'name' => 'Ram Sharma',
            'phone' => '9841000002',
            'email' => 'ram@test.com',
            'loyalty_points' => 0,
            'is_active' => true,
        ];

        return Customer::create(array_merge($defaults, $overrides));
    }

    protected function createSupplier(Company $company, array $overrides = []): Supplier
    {
        $defaults = [
            'company_id' => $company->id,
            'name' => 'Nepal Pharma Distributors',
            'contact_person' => 'Hari Prasad',
            'phone' => '9841000003',
            'email' => 'supplier@test.com',
            'is_active' => true,
        ];

        return Supplier::create(array_merge($defaults, $overrides));
    }

    protected function createPaymentMethod(Company $company, array $overrides = []): object
    {
        $defaults = [
            'company_id' => $company->id,
            'name' => 'Cash',
            'type' => 'cash',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('payment_methods')->insertGetId(array_merge($defaults, $overrides));

        return DB::table('payment_methods')->where('id', $id)->first();
    }

    protected function createRegister(Company $company, Outlet $outlet, User $user, array $overrides = []): Register
    {
        $defaults = [
            'company_id' => $company->id,
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'opening_balance' => 5000.00,
            'status' => 'open',
            'opened_at' => now(),
        ];

        return Register::create(array_merge($defaults, $overrides));
    }

    protected function createManufacturer(Company $company, array $overrides = []): Manufacturer
    {
        $defaults = [
            'company_id' => $company->id,
            'name' => 'Micro Labs',
            'is_active' => true,
        ];

        return Manufacturer::create(array_merge($defaults, $overrides));
    }

    protected function createCategory(Company $company, array $overrides = []): MedicineCategory
    {
        $defaults = [
            'company_id' => $company->id,
            'name' => 'Analgesics',
            'is_active' => true,
        ];

        return MedicineCategory::create(array_merge($defaults, $overrides));
    }

    protected function createFullTestData(): array
    {
        $company = $this->createCompany();
        $outlet = $this->createOutlet($company);
        $user = $this->createUser($company, $outlet);
        $customer = $this->createCustomer($company);
        $supplier = $this->createSupplier($company);
        $paymentMethod = $this->createPaymentMethod($company);
        $register = $this->createRegister($company, $outlet, $user);

        return compact('company', 'outlet', 'user', 'customer', 'supplier', 'paymentMethod', 'register');
    }
}
