<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PosController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        $today = now()->startOfDay();

        $salesToday = DB::table('sales')
            ->where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->where('created_at', '>=', $today)
            ->count();

        $revenueToday = DB::table('sales')
            ->where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->where('created_at', '>=', $today)
            ->sum('total_amount');

        $itemsSoldToday = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.outlet_id', $outletId)
            ->where('sales.created_at', '>=', $today)
            ->sum('sale_items.quantity');

        $topMedicines = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('medicines', 'medicines.id', '=', 'sale_items.medicine_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.outlet_id', $outletId)
            ->where('sales.created_at', '>=', $today)
            ->selectRaw('medicines.id, medicines.brand_name, SUM(sale_items.quantity) as total_sold')
            ->groupBy('medicines.id', 'medicines.brand_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'sales_today' => (int) $salesToday,
                'revenue_today' => (float) $revenueToday,
                'items_sold_today' => (int) $itemsSoldToday,
                'top_medicines' => $topMedicines,
            ],
        ]);
    }

    public function recentSales(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        $today = now()->startOfDay();

        $sales = DB::table('sales')
            ->where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->where('created_at', '>=', $today)
            ->whereNotNull('invoice_number')
            ->select([
                'id', 'invoice_number', 'total_amount', 'paid_amount',
                'due_amount', 'payment_status', 'customer_id', 'created_at',
            ])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Eager-load customer names
        $customerIds = $sales->pluck('customer_id')->filter()->unique();
        $customers = DB::table('customers')
            ->whereIn('id', $customerIds)
            ->select('id', 'name')
            ->get()
            ->keyBy('id');

        $sales = $sales->map(fn ($sale) => [
            'id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'total_amount' => (float) $sale->total_amount,
            'paid_amount' => (float) $sale->paid_amount,
            'due_amount' => (float) $sale->due_amount,
            'payment_status' => $sale->payment_status,
            'created_at' => $sale->created_at,
            'customer_name' => $customers->get($sale->customer_id)?->name ?? 'Walk-in',
        ]);

        return response()->json([
            'success' => true,
            'data' => $sales,
        ]);
    }
}
