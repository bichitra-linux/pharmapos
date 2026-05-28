<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SuperAdmin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() instanceof SuperAdmin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return $next($request);
    }
}
