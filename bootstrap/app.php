<?php

declare(strict_types=1);

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckTenantNotSuspended;
use App\Http\Middleware\EnsureCompanyIsActive;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'company.active' => EnsureCompanyIsActive::class,
            'tenant.active' => CheckTenantNotSuspended::class,
            'tenant.context' => SetTenantContext::class,
            'permission' => CheckPermission::class,
            'superadmin' => EnsureSuperAdmin::class,
        ]);

        $middleware->api(prepend: [
            SecurityHeaders::class,
            SetTenantContext::class,
        ]);

        $middleware->group('auth.tenant', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
        ]);

        $middleware->group('perm.medicines', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
            'permission:manage_medicines',
        ]);

        $middleware->group('perm.inventory', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
            'permission:manage_inventory',
        ]);

        $middleware->group('perm.sales', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
            'permission:manage_sales',
        ]);

        $middleware->group('perm.customers', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
            'permission:manage_customers',
        ]);

        $middleware->group('perm.purchases', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
            'permission:manage_purchases',
        ]);

        $middleware->group('perm.prescriptions', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
            'permission:manage_prescriptions',
        ]);

        $middleware->group('perm.reports', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
            'permission:view_reports',
        ]);

        $middleware->group('perm.settings', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
            'permission:manage_settings',
        ]);

        $middleware->group('perm.users', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
            'permission:manage_users',
        ]);

        $middleware->group('perm.billing', [
            'auth:sanctum', 'tenant.active', 'company.active', 'throttle:120,1',
            'permission:manage_billing',
        ]);

        // $middleware->statefulApi(); // removed: client uses bearer tokens only
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
