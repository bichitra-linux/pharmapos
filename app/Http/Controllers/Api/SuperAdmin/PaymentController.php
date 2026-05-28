<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SubscriptionPayment::with(['company', 'plan']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        $payments = $query->latest()->paginate($request->input('per_page', 15));

        return $this->paginated($payments);
    }

    public function revenue(): JsonResponse
    {
        $totalRevenue = SubscriptionPayment::where('status', 'completed')
            ->sum('amount');

        $monthlyRevenue = SubscriptionPayment::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('SUM(amount) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $yearlyRevenue = SubscriptionPayment::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('SUM(amount) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', 'completed')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        $currentMonth = SubscriptionPayment::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $previousMonth = SubscriptionPayment::where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        $growthRate = $previousMonth > 0
            ? round((($currentMonth - $previousMonth) / $previousMonth) * 100, 2)
            : 0;

        return $this->success([
            'total_revenue' => (float) $totalRevenue,
            'current_month' => (float) $currentMonth,
            'previous_month' => (float) $previousMonth,
            'growth_rate' => $growthRate,
            'monthly' => $monthlyRevenue,
            'yearly' => $yearlyRevenue,
        ]);
    }
}
