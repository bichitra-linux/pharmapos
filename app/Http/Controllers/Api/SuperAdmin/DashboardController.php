<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\SubscriptionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalTenants = Company::count();
        $activeTenants = Company::whereNull('suspended_at')->where('is_active', true)->count();
        $suspendedTenants = Company::whereNotNull('suspended_at')->count();
        $newThisMonth = Company::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $mrr = Company::whereNull('suspended_at')
            ->where('is_active', true)
            ->whereNotNull('subscription_plan_id')
            ->join('subscription_plans', 'companies.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price_monthly');

        $expiringSoon = Company::whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [now(), now()->addDays(7)])
            ->count();

        $monthlyRevenue = SubscriptionPayment::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('SUM(amount) as total')
        )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $recentTenants = Company::query()->latest()->take(10)->get();

        // Churn & renewal forecast
        $expiringSoon7 = Company::whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [now(), now()->addDays(7)])->count();
        $expiringSoon14 = Company::whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [now(), now()->addDays(14)])->count();
        $expiringSoon30 = Company::whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [now(), now()->addDays(30)])->count();

        $activeStartOfMonth = Company::whereNull('suspended_at')->where('is_active', true)
            ->where('created_at', '<=', now()->startOfMonth())->count();
        $cancelledThisMonth = SubscriptionPayment::where('status', 'cancelled')
            ->whereMonth('created_at', now()->month)->count();
        $churnRate = $activeStartOfMonth > 0
            ? round(($cancelledThisMonth / $activeStartOfMonth) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'new_this_month' => $newThisMonth,
                'mrr' => (float) $mrr,
                'expiring_soon' => $expiringSoon,
                'expiring_soon_7' => $expiringSoon7,
                'expiring_soon_14' => $expiringSoon14,
                'expiring_soon_30' => $expiringSoon30,
                'churn_rate' => $churnRate,
                'cancelled_this_month' => $cancelledThisMonth,
                'monthly_revenue' => $monthlyRevenue,
                'recent_tenants' => $recentTenants,
            ],
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::withoutGlobalScopes()
            ->with(['company:id,name', 'user:id,name']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->latest('created_at')
            ->paginate($request->get('per_page', 25));

        return $this->paginated($logs);
    }
}
