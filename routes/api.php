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
Route::get('public/stats', [PublicLandingPageController::class, 'stats']);
Route::get('public/plans', [PublicLandingPageController::class, 'plans']);

// Protected routes
Route::middleware(['auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1'])->group(function (): void {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('auth/password', [AuthController::class, 'changePassword']);
    Route::get('auth/outlets', [AuthController::class, 'outlets']);
    Route::put('auth/active-outlet', [AuthController::class, 'switchOutlet']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/expiry-alerts', [DashboardController::class, 'expiryAlerts']);
    Route::get('dashboard/low-stock', [DashboardController::class, 'lowStock']);
    Route::get('dashboard/sales-chart', [DashboardController::class, 'salesChart']);
    Route::get('dashboard/top-medicines', [DashboardController::class, 'topMedicines']);

    // POS Dashboard
    Route::prefix('pos')->group(function (): void {
        Route::get('stats', [PosController::class, 'stats']);
        Route::get('recent-sales', [PosController::class, 'recentSales']);
    });

    // Medicines
    Route::get('medicines/search', [MedicineController::class, 'search']);
    Route::post('medicines/import', [MedicineController::class, 'import']);
    Route::post('medicines/bulk-price-update', [MedicineController::class, 'bulkPriceUpdate'])->middleware('permission:manage_medicines');
    Route::apiResource('medicines', MedicineController::class)->middleware('permission:manage_medicines');
    Route::get('medicines/{medicine}/batches', [MedicineController::class, 'batches']);
    Route::get('medicines/{medicine}/substitutes', [MedicineController::class, 'substitutes']);

    // Batches
    Route::get('batches/expiring-soon', [BatchController::class, 'expiringSoon']);
    Route::apiResource('batches', BatchController::class)->only(['index', 'store', 'show', 'update'])->middleware('permission:manage_inventory');

    // Categories
    Route::apiResource('categories', MedicineCategoryController::class)->middleware('permission:manage_medicines');

    // Manufacturers
    Route::apiResource('manufacturers', ManufacturerController::class)->middleware('permission:manage_medicines');

    // Salt Compositions
    Route::apiResource('salt-compositions', SaltCompositionController::class)->middleware('permission:manage_medicines');

    // Sales
    Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice']);
    Route::get('sales/daily-summary', [SaleController::class, 'dailySummary']);
    Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show'])->middleware('permission:manage_sales');

    // Sale Returns
    Route::apiResource('sale-returns', SaleReturnController::class)->only(['index', 'store'])->middleware('permission:manage_sales');

    // Prescriptions
    Route::post('prescriptions/{prescription}/dispense', [PrescriptionController::class, 'dispense'])->middleware('permission:manage_prescriptions');
    Route::apiResource('prescriptions', PrescriptionController::class)->middleware('permission:manage_prescriptions');

    // Customers
    Route::get('customers/search', [CustomerController::class, 'search']);
    Route::get('customers/{customer}/history', [CustomerController::class, 'history']);
    Route::post('customers/{customer}/credit-lend', [CustomerController::class, 'creditLend'])->middleware('permission:manage_customers');
    Route::post('customers/{customer}/credit-receive', [CustomerController::class, 'creditReceive'])->middleware('permission:manage_customers');
    Route::get('customers/{customer}/credit-ledger', [CustomerController::class, 'creditLedger'])->middleware('permission:manage_customers');
    Route::get('customers/{customer}/credit-summary', [CustomerController::class, 'creditSummary']);
    Route::post('customers/{customer}/credit-limit', [CustomerController::class, 'setCreditLimit'])->middleware('permission:manage_customers');
    Route::apiResource('customers', CustomerController::class)->middleware('permission:manage_customers');

    // Suppliers
    Route::get('suppliers/{supplier}/ledger', [SupplierController::class, 'ledger']);
    Route::apiResource('suppliers', SupplierController::class)->middleware('permission:manage_purchases');

    // Purchases
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->middleware('permission:manage_purchases');
    Route::apiResource('purchases', PurchaseController::class)->only(['index', 'store', 'show', 'update'])->middleware('permission:manage_purchases');

    // Supplier Payments
    Route::apiResource('supplier-payments', SupplierPaymentController::class)->only(['index', 'store'])->middleware('permission:manage_purchases');

    // Supplier Returns
    Route::apiResource('supplier-returns', SupplierReturnController::class)->only(['index', 'store'])->middleware('permission:manage_purchases');

    // Inventory
    Route::get('inventory/stock', [InventoryController::class, 'stock'])->middleware('permission:view_inventory');
    Route::get('inventory/adjustments', [InventoryController::class, 'adjustments'])->middleware('permission:manage_inventory');
    Route::post('inventory/adjustments', [InventoryController::class, 'storeAdjustment'])->middleware('permission:manage_inventory');
    Route::get('inventory/reorder-suggestions', [InventoryController::class, 'reorderSuggestions'])->middleware('permission:view_inventory');

    // Payment Methods
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);

    // Payments (online gateways)
    Route::post('payments/esewa', [PaymentController::class, 'initiateEsewa'])->middleware('permission:manage_sales');
    Route::post('payments/khalti', [PaymentController::class, 'initiateKhalti'])->middleware('permission:manage_sales');
    Route::post('payments/fonepay', [PaymentController::class, 'initiateFonepay'])->middleware('permission:manage_sales');
    Route::post('payments/connectips', [PaymentController::class, 'initiateConnectIPS'])->middleware('permission:manage_sales');

    // Reports
    Route::prefix('reports')->group(function (): void {
        Route::get('sales', [ReportController::class, 'sales'])->middleware('permission:view_reports');
        Route::get('purchases', [ReportController::class, 'purchase'])->middleware('permission:view_reports');
        Route::get('inventory', [ReportController::class, 'inventory'])->middleware('permission:view_reports');
        Route::get('expiry', [ReportController::class, 'expiry'])->middleware('permission:view_reports');
        Route::get('profit-loss', [ReportController::class, 'profitLoss'])->middleware('permission:view_reports');
        Route::get('vat', [ReportController::class, 'vat'])->middleware('permission:view_reports');
        Route::get('narcotics', [ReportController::class, 'narcotics'])->middleware('permission:view_reports');
        Route::get('dead-stock', [ReportController::class, 'deadStock'])->middleware('permission:view_reports');
        Route::get('supplier-due', [ReportController::class, 'supplierDue'])->middleware('permission:view_reports');
        Route::get('customer-due', [ReportController::class, 'customerDue'])->middleware('permission:view_reports');
        Route::get('schedule-wise', [ReportController::class, 'scheduleWise'])->middleware('permission:view_reports');
        Route::get('category-wise', [ReportController::class, 'categoryWise'])->middleware('permission:view_reports');
    });

    // Narcotics Register
    Route::apiResource('narcotics-register', NarcoticsRegisterController::class)->only(['index', 'store'])->middleware('permission:manage_prescriptions');

    // Users
    Route::apiResource('users', UserController::class)->middleware('permission:manage_users');

    // Roles (read-only, built-in only)
    Route::get('roles', [RoleController::class, 'index']);

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->middleware('permission:manage_settings');
    Route::put('settings', [SettingController::class, 'update'])->middleware('permission:manage_settings');

    // Company
    Route::get('company', [CompanyController::class, 'show']);
    Route::put('company', [CompanyController::class, 'update'])->middleware('permission:manage_settings');

    // Subscription
    Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::get('subscriptions/gateways', [SubscriptionController::class, 'gateways']);
    Route::get('subscriptions/status', [SubscriptionController::class, 'status']);
    Route::post('subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel']);
});
