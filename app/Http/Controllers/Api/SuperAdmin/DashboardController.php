<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SubscriptionPayment;
use Illuminate\Http\JsonResponse;
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

        $recentTenants = Company::withoutGlobalScopes()->latest()->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'new_this_month' => $newThisMonth,
                'mrr' => (float) $mrr,
                'expiring_soon' => $expiringSoon,
                'monthly_revenue' => $monthlyRevenue,
                'recent_tenants' => $recentTenants,
            ],
        ]);
    }
}
