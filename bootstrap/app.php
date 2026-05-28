<?php

declare(strict_types=1);

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckTenantNotSuspended;
use App\Http\Middleware\EnsureCompanyIsActive;
use App\Http\Middleware\EnsureSuperAdmin;
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
            SetTenantContext::class,
        ]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
