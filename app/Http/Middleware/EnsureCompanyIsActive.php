<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCompanyIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $company = $user->company ?? null;

        if (! $company) {
            return response()->json(['message' => 'No company associated with this account.'], 403);
        }

        if (! $company->is_active) {
            return response()->json([
                'message' => 'Company account is suspended. Contact support.',
                'suspension_reason' => $company->suspension_reason,
            ], 403);
        }

        if (! $company->isSubscriptionActive()) {
            if ($company->isSuspended()) {
                return response()->json([
                    'message' => 'Account suspended.',
                    'suspension_reason' => $company->suspension_reason,
                ], 403);
            }

            return response()->json(['message' => 'Subscription has expired. Please renew.'], 403);
        }

        return $next($request);
    }
}
