<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ManufacturerController;
use App\Http\Controllers\Api\MedicineCategoryController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\NarcoticsRegisterController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleReturnController;
use App\Http\Controllers\Api\SaltCompositionController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SupplierPaymentController;
use App\Http\Controllers\Api\SupplierReturnController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SuperAdmin\AuthController as SuperAdminAuthController;
use App\Http\Controllers\Api\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\Api\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\Api\SuperAdmin\PlanController as SuperAdminPlanController;
use App\Http\Controllers\Api\SuperAdmin\SubscriptionController as SuperAdminSubscriptionController;
use App\Http\Controllers\Api\SuperAdmin\PaymentController as SuperAdminPaymentController;
use App\Http\Controllers\Api\SuperAdmin\SettingController as SuperAdminSettingController;
use App\Http\Controllers\Api\SuperAdmin\SystemController as SuperAdminSystemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api and require Sanctum authentication.
| Company context is set via SetTenantContext middleware.
|
*/

// Super Admin routes
Route::prefix('super-admin')->group(function () {
    Route::post('auth/login', [SuperAdminAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'superadmin'])->group(function () {
        Route::post('auth/logout', [SuperAdminAuthController::class, 'logout']);
        Route::get('auth/me', [SuperAdminAuthController::class, 'me']);

        Route::get('dashboard', [SuperAdminDashboardController::class, 'index']);

        Route::apiResource('tenants', SuperAdminTenantController::class);
        Route::patch('tenants/{tenant}/suspend', [SuperAdminTenantController::class, 'suspend']);
        Route::patch('tenants/{tenant}/activate', [SuperAdminTenantController::class, 'activate']);

        Route::apiResource('plans', SuperAdminPlanController::class);
        Route::patch('plans/{plan}/toggle', [SuperAdminPlanController::class, 'toggle']);

        Route::get('subscriptions', [SuperAdminSubscriptionController::class, 'index']);
        Route::post('subscriptions/{company}/extend', [SuperAdminSubscriptionController::class, 'extend']);
        Route::post('subscriptions/{company}/cancel', [SuperAdminSubscriptionController::class, 'cancel']);

        Route::get('payments', [SuperAdminPaymentController::class, 'index']);
        Route::get('payments/revenue', [SuperAdminPaymentController::class, 'revenue']);

        Route::get('settings', [SuperAdminSettingController::class, 'index']);
        Route::put('settings', [SuperAdminSettingController::class, 'update']);

        Route::get('system/health', [SuperAdminSystemController::class, 'health']);
        Route::post('system/clear-cache', [SuperAdminSystemController::class, 'clearCache']);
    });
});

// Public routes
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:3,1');

// Payment callbacks (public - called by payment gateways)
Route::post('payments/callback/{gateway}', [PaymentController::class, 'callback']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function (): void {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('auth/password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/expiry-alerts', [DashboardController::class, 'expiryAlerts']);
    Route::get('dashboard/low-stock', [DashboardController::class, 'lowStock']);
    Route::get('dashboard/sales-chart', [DashboardController::class, 'salesChart']);
    Route::get('dashboard/top-medicines', [DashboardController::class, 'topMedicines']);

    // Medicines
    Route::get('medicines/search', [MedicineController::class, 'search']);
    Route::post('medicines/import', [MedicineController::class, 'import']);
    Route::apiResource('medicines', MedicineController::class);
    Route::get('medicines/{medicine}/batches', [MedicineController::class, 'batches']);
    Route::get('medicines/{medicine}/substitutes', [MedicineController::class, 'substitutes']);

    // Batches
    Route::get('batches/expiring-soon', [BatchController::class, 'expiringSoon']);
    Route::apiResource('batches', BatchController::class)->only(['index', 'store', 'show', 'update']);

    // Categories
    Route::apiResource('categories', MedicineCategoryController::class);

    // Manufacturers
    Route::apiResource('manufacturers', ManufacturerController::class);

    // Salt Compositions
    Route::apiResource('salt-compositions', SaltCompositionController::class);

    // Sales
    Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice']);
    Route::get('sales/daily-summary', [SaleController::class, 'dailySummary']);
    Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show']);

    // Sale Returns
    Route::apiResource('sale-returns', SaleReturnController::class)->only(['index', 'store']);

    // Prescriptions
    Route::post('prescriptions/{prescription}/dispense', [PrescriptionController::class, 'dispense']);
    Route::apiResource('prescriptions', PrescriptionController::class);

    // Customers
    Route::get('customers/{customer}/history', [CustomerController::class, 'history']);
    Route::apiResource('customers', CustomerController::class);

    // Suppliers
    Route::get('suppliers/{supplier}/ledger', [SupplierController::class, 'ledger']);
    Route::apiResource('suppliers', SupplierController::class);

    // Purchases
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
    Route::apiResource('purchases', PurchaseController::class)->only(['index', 'store', 'show', 'update']);

    // Supplier Payments
    Route::apiResource('supplier-payments', SupplierPaymentController::class)->only(['index', 'store']);

    // Supplier Returns
    Route::apiResource('supplier-returns', SupplierReturnController::class)->only(['index', 'store']);

    // Inventory
    Route::get('inventory/stock', [InventoryController::class, 'stock']);
    Route::get('inventory/adjustments', [InventoryController::class, 'adjustments']);
    Route::post('inventory/adjustments', [InventoryController::class, 'storeAdjustment']);

    // Payment Methods
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);

    // Payments (online gateways)
    Route::post('payments/esewa', [PaymentController::class, 'initiateEsewa']);
    Route::post('payments/khalti', [PaymentController::class, 'initiateKhalti']);
    Route::post('payments/fonepay', [PaymentController::class, 'initiateFonepay']);
    Route::post('payments/connectips', [PaymentController::class, 'initiateConnectIPS']);

    // Reports
    Route::prefix('reports')->group(function (): void {
        Route::get('sales', [ReportController::class, 'sales']);
        Route::get('purchases', [ReportController::class, 'purchase']);
        Route::get('inventory', [ReportController::class, 'inventory']);
        Route::get('expiry', [ReportController::class, 'expiry']);
        Route::get('profit-loss', [ReportController::class, 'profitLoss']);
        Route::get('vat', [ReportController::class, 'vat']);
        Route::get('narcotics', [ReportController::class, 'narcotics']);
        Route::get('dead-stock', [ReportController::class, 'deadStock']);
        Route::get('supplier-due', [ReportController::class, 'supplierDue']);
        Route::get('customer-due', [ReportController::class, 'customerDue']);
        Route::get('schedule-wise', [ReportController::class, 'scheduleWise']);
        Route::get('category-wise', [ReportController::class, 'categoryWise']);
    });

    // Narcotics Register
    Route::apiResource('narcotics-register', NarcoticsRegisterController::class)->only(['index', 'store']);

    // Users
    Route::apiResource('users', UserController::class);

    // Roles
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::put('roles/{role}', [RoleController::class, 'update']);

    // Settings
    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);

    // Company
    Route::get('company', [CompanyController::class, 'show']);
    Route::put('company', [CompanyController::class, 'update']);

    // Subscription
    Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::get('subscriptions/status', [SubscriptionController::class, 'status']);
    Route::post('subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel']);
});
