<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Company;
use App\Models\CreditLedger;
use App\Models\Customer;
use App\Models\CustomerReturn;
use App\Models\InventoryAdjustment;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Outlet;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Observers\CompanyObserver;
use App\Observers\CreditLedgerObserver;
use App\Observers\CustomerReturnObserver;
use App\Observers\InventoryAdjustmentObserver;
use App\Observers\OutletObserver;
use App\Observers\PrescriptionItemObserver;
use App\Observers\PrescriptionObserver;
use App\Observers\SaleItemObserver;
use App\Observers\SaleObserver;
use App\Observers\UserObserver;
use App\Policies\CompanyPolicy;
use App\Policies\CreditLedgerPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\CustomerReturnPolicy;
use App\Policies\InventoryAdjustmentPolicy;
use App\Policies\MedicineBatchPolicy;
use App\Policies\MedicinePolicy;
use App\Policies\OutletPolicy;
use App\Policies\PrescriptionItemPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\RegisterPolicy;
use App\Policies\SalePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::unguard(false);
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(function (): Password {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->uncompromised();
        });

        $this->registerObservers();

        $this->registerPolicies();
    }

    private function registerObservers(): void
    {
        Sale::observe(SaleObserver::class);
        SaleItem::observe(SaleItemObserver::class);
        Prescription::observe(PrescriptionObserver::class);
        PrescriptionItem::observe(PrescriptionItemObserver::class);
        CustomerReturn::observe(CustomerReturnObserver::class);
        InventoryAdjustment::observe(InventoryAdjustmentObserver::class);
        CreditLedger::observe(CreditLedgerObserver::class);
        User::observe(UserObserver::class);
        Company::observe(CompanyObserver::class);
        Outlet::observe(OutletObserver::class);
    }

    private function registerPolicies(): void
    {
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Prescription::class, PrescriptionPolicy::class);
        Gate::policy(PrescriptionItem::class, PrescriptionItemPolicy::class);
        Gate::policy(CustomerReturn::class, CustomerReturnPolicy::class);
        Gate::policy(InventoryAdjustment::class, InventoryAdjustmentPolicy::class);
        Gate::policy(CreditLedger::class, CreditLedgerPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Outlet::class, OutletPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Medicine::class, MedicinePolicy::class);
        Gate::policy(MedicineBatch::class, MedicineBatchPolicy::class);
        Gate::policy(Register::class, RegisterPolicy::class);
        Gate::policy(\App\Models\Supplier::class, SupplierPolicy::class);
    }
}
