<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        $today = Carbon::today();

        $todaySales = Sale::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->whereDate('created_at', $today)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as revenue, COALESCE(SUM(discount_amount), 0) as discount')
            ->first();

        $customerCount = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $medicineCount = Medicine::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $totalStock = DB::table('medicine_batches')
            ->join('medicines', 'medicines.id', '=', 'medicine_batches.medicine_id')
            ->where('medicines.company_id', $companyId)
            ->where('medicine_batches.outlet_id', $outletId)
            ->where('medicine_batches.quantity_in_stock', '>', 0)
            ->sum('medicine_batches.quantity_in_stock');

        return response()->json([
            'success' => true,
            'data' => [
                'today_sales' => [
                    'count' => (int) $todaySales->count,
                    'revenue' => (float) $todaySales->revenue,
                    'discount' => (float) $todaySales->discount,
                ],
                'customer_count' => $customerCount,
                'medicine_count' => $medicineCount,
                'total_stock' => (float) $totalStock,
            ],
        ]);
    }

    public function expiryAlerts(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        $days = (int) $request->get('days', 90);

        $batches = DB::table('medicine_batches')
            ->join('medicines', 'medicines.id', '=', 'medicine_batches.medicine_id')
            ->where('medicines.company_id', $companyId)
            ->where('medicine_batches.outlet_id', $outletId)
            ->where('medicine_batches.quantity_in_stock', '>', 0)
            ->whereDate('medicine_batches.expiry_date', '<=', Carbon::today()->addDays($days))
            ->whereDate('medicine_batches.expiry_date', '>=', Carbon::today())
            ->select(
                'medicine_batches.id',
                'medicine_batches.batch_number',
                'medicine_batches.expiry_date',
                'medicine_batches.quantity_in_stock',
                'medicines.brand_name',
                'medicines.generic_name',
            )
            ->orderBy('medicine_batches.expiry_date')
            ->get()
            ->map(fn ($batch) => [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date,
                'quantity' => (float) $batch->quantity_in_stock,
                'brand_name' => $batch->brand_name,
                'generic_name' => $batch->generic_name,
                'days_remaining' => Carbon::parse($batch->expiry_date)->diffInDays(Carbon::today()),
            ]);

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $company = Company::find($companyId);
        $lowStockThreshold = (int) ($company->settings['low_stock_threshold'] ?? 10);

        $medicines = Medicine::where('medicines.company_id', $companyId)
            ->where('medicines.is_active', true)
            ->select(
                'medicines.id',
                'medicines.brand_name',
                'medicines.generic_name',
                'medicines.hsn_code',
            )
            ->selectRaw('COALESCE(SUM(medicine_batches.quantity_in_stock), 0) as current_stock')
            ->leftJoin('medicine_batches', function ($join) use ($outletId) {
                $join->on('medicine_batches.medicine_id', '=', 'medicines.id')
                    ->where('medicine_batches.outlet_id', '=', $outletId);
            })
            ->groupBy('medicines.id', 'medicines.brand_name', 'medicines.generic_name', 'medicines.hsn_code')
            ->having('current_stock', '>', 0)
            ->having('current_stock', '<=', $lowStockThreshold)
            ->orderBy('current_stock')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $medicines,
        ]);
    }

    public function salesChart(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $sales = Sale::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->where('created_at', '>=', Carbon::today()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chart = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $sale = $sales->firstWhere('date', $date);
            $chart->push([
                'date' => $date,
                'count' => $sale ? (int) $sale->count : 0,
                'revenue' => $sale ? (float) $sale->revenue : 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $chart,
        ]);
    }

    public function topMedicines(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $topMedicines = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('medicines', 'medicines.id', '=', 'sale_items.medicine_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.outlet_id', $outletId)
            ->where('sales.created_at', '>=', Carbon::today()->startOfMonth())
            ->select(
                'medicines.id',
                'medicines.brand_name',
                'medicines.generic_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total) as total_revenue'),
            )
            ->groupBy('medicines.id', 'medicines.brand_name', 'medicines.generic_name')
            ->orderByDesc('total_quantity')
            ->limit((int) $request->get('limit', 10))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topMedicines,
        ]);
    }
}
