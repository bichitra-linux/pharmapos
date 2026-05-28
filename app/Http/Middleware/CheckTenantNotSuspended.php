<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'company') && $user->company) {
            if ($user->company->isSuspended()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account suspended',
                    'suspension_reason' => $user->company->suspension_reason,
                ], 403);
            }
        }

        return $next($request);
    }
}
