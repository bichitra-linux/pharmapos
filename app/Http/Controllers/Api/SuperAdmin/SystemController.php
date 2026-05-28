<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class SystemController extends Controller
{
    public function health(): JsonResponse
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            Cache::store()->getConnection();
            $checks['cache'] = ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            Queue::size();
            $checks['queue'] = ['status' => 'ok', 'message' => 'Operational'];
        } catch (\Exception $e) {
            $checks['queue'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        $checks['php_version'] = ['status' => 'ok', 'message' => PHP_VERSION];
        $checks['laravel_version'] = ['status' => 'ok', 'message' => app()->version()];

        $allOk = collect($checks)->every(fn ($check) => $check['status'] === 'ok');

        return response()->json([
            'success' => $allOk,
            'data' => [
                'status' => $allOk ? 'healthy' : 'degraded',
                'checks' => $checks,
                'timestamp' => now()->toIso8601String(),
            ],
        ], $allOk ? 200 : 503);
    }

    public function clearCache(): JsonResponse
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return $this->success([
            'cache' => Artisan::output(),
        ], 'Cache cleared successfully.');
    }
}
