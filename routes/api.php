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
use App\Http\Controllers\Api\PosController;
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
use App\Http\Controllers\Api\SuperAdmin\PaymentGatewayController as SuperAdminPaymentGatewayController;
use App\Http\Controllers\Api\SuperAdmin\LandingPageController as SuperAdminLandingPageController;
use App\Http\Controllers\Public\LandingPageController as PublicLandingPageController;
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

        Route::get('tenants/{tenant}/usage', [SuperAdminTenantController::class, 'usage']);
        Route::post('tenants/{tenant}/impersonate', [SuperAdminTenantController::class, 'impersonate']);
        Route::post('impersonation/stop', [SuperAdminTenantController::class, 'stopImpersonation']);
        Route::patch('tenants/{tenant}/suspend', [SuperAdminTenantController::class, 'suspend']);
        Route::patch('tenants/{tenant}/activate', [SuperAdminTenantController::class, 'activate']);
        Route::apiResource('tenants', SuperAdminTenantController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

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

        // Audit logs
        Route::get('audit-logs', [SuperAdminDashboardController::class, 'auditLogs']);

        // Payment Gateways
        Route::get('payment-gateways', [SuperAdminPaymentGatewayController::class, 'index']);
        Route::post('payment-gateways', [SuperAdminPaymentGatewayController::class, 'store']);
        Route::put('payment-gateways/{gateway}', [SuperAdminPaymentGatewayController::class, 'update']);
        Route::patch('payment-gateways/{gateway}/toggle', [SuperAdminPaymentGatewayController::class, 'toggle']);
        Route::post('payment-gateways/{gateway}/test', [SuperAdminPaymentGatewayController::class, 'test']);

        // Landing page management
        Route::get('landing', [SuperAdminLandingPageController::class, 'show']);
        Route::put('landing', [SuperAdminLandingPageController::class, 'update']);
        Route::post('landing/publish', [SuperAdminLandingPageController::class, 'publish']);
        Route::get('landing/revisions', [SuperAdminLandingPageController::class, 'revisions']);
        Route::post('landing/revisions/{revision}/restore', [SuperAdminLandingPageController::class, 'restore']);
    });
});

// Public routes
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:3,1');

// Payment callbacks (public - called by payment gateways)
Route::post('payments/callback/{gateway}', [PaymentController::class, 'callback'])->middleware('throttle:20,1');

// Public landing page data
Route::get('public/plans', [PublicLandingPageController::class, 'plans']);

// Protected routes
Route::middleware('auth.tenant')->group(function (): void {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('auth/password', [AuthController::class, 'changePassword']);
    Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('auth/outlets', [AuthController::class, 'outlets']);
    Route::put('auth/active-outlet', [AuthController::class, 'switchOutlet']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/expiry-alerts', [DashboardController::class, 'expiryAlerts']);
    Route::get('dashboard/low-stock', [DashboardController::class, 'lowStock']);
    Route::get('dashboard/sales-chart', [DashboardController::class, 'salesChart']);
    Route::get('dashboard/top-medicines', [DashboardController::class, 'topMedicines']);

    // POS dashboard (read-only views)
    Route::prefix('pos')->group(function (): void {
        Route::get('stats', [PosController::class, 'stats']);
        Route::get('recent-sales', [PosController::class, 'recentSales']);
    });

    // Read-only resource helpers (no permission gate)
    Route::get('medicines/search', [MedicineController::class, 'search']);
    Route::post('medicines/import', [MedicineController::class, 'import']);
    Route::get('medicines/{medicine}/batches', [MedicineController::class, 'batches']);
    Route::get('medicines/{medicine}/substitutes', [MedicineController::class, 'substitutes']);
    Route::get('batches/expiring-soon', [BatchController::class, 'expiringSoon']);
    Route::get('customers/search', [CustomerController::class, 'search']);
    Route::get('customers/{customer}/history', [CustomerController::class, 'history']);
    Route::get('customers/{customer}/credit-summary', [CustomerController::class, 'creditSummary']);
    Route::get('suppliers/{supplier}/ledger', [SupplierController::class, 'ledger']);
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('company', [CompanyController::class, 'show']);
    Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::get('subscriptions/gateways', [SubscriptionController::class, 'gateways']);
    Route::get('subscriptions/status', [SubscriptionController::class, 'status']);
    Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice']);
    Route::get('sales/daily-summary', [SaleController::class, 'dailySummary']);
});

// Permission-gated routes
Route::middleware('perm.medicines')->group(function (): void {
    Route::post('medicines/bulk-price-update', [MedicineController::class, 'bulkPriceUpdate']);
    Route::apiResource('medicines', MedicineController::class);
    Route::apiResource('categories', MedicineCategoryController::class);
    Route::apiResource('manufacturers', ManufacturerController::class);
    Route::apiResource('salt-compositions', SaltCompositionController::class);
});

Route::middleware('perm.inventory')->group(function (): void {
    Route::get('inventory/adjustments', [InventoryController::class, 'adjustments']);
    Route::post('inventory/adjustments', [InventoryController::class, 'storeAdjustment']);
    Route::apiResource('batches', BatchController::class)->only(['index', 'store', 'show', 'update']);
});

Route::middleware('perm.sales')->group(function (): void {
    Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show']);
    Route::apiResource('sale-returns', SaleReturnController::class)->only(['index', 'store']);
    Route::post('payments/{gateway}/initiate', [PaymentController::class, 'initiate']);
});

Route::middleware('perm.customers')->group(function (): void {
    Route::post('customers/{customer}/credit-lend', [CustomerController::class, 'creditLend']);
    Route::post('customers/{customer}/credit-receive', [CustomerController::class, 'creditReceive']);
    Route::get('customers/{customer}/credit-ledger', [CustomerController::class, 'creditLedger']);
    Route::post('customers/{customer}/credit-limit', [CustomerController::class, 'setCreditLimit']);
    Route::apiResource('customers', CustomerController::class);
});

Route::middleware('perm.purchases')->group(function (): void {
    Route::apiResource('suppliers', SupplierController::class);
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
    Route::apiResource('purchases', PurchaseController::class)->only(['index', 'store', 'show', 'update']);
    Route::apiResource('supplier-payments', SupplierPaymentController::class)->only(['index', 'store']);
    Route::apiResource('supplier-returns', SupplierReturnController::class)->only(['index', 'store']);
});

Route::middleware('perm.prescriptions')->group(function (): void {
    Route::post('prescriptions/{prescription}/dispense', [PrescriptionController::class, 'dispense']);
    Route::apiResource('prescriptions', PrescriptionController::class);
    Route::apiResource('narcotics-register', NarcoticsRegisterController::class)->only(['index', 'store']);
});

Route::middleware('perm.reports')->prefix('reports')->group(function (): void {
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

Route::middleware('perm.settings')->group(function (): void {
    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);
    Route::put('company', [CompanyController::class, 'update']);
    Route::get('inventory/stock', [InventoryController::class, 'stock']);
    Route::get('inventory/reorder-suggestions', [InventoryController::class, 'reorderSuggestions']);
});

Route::middleware('perm.users')->group(function (): void {
    Route::apiResource('users', UserController::class);
});

Route::middleware('perm.billing')->group(function (): void {
    Route::post('subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel']);
});
