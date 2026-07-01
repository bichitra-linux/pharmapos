<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReportController extends Controller
{
    public function sales(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $query = Sale::where('company_id', $companyId)
            ->where('outlet_id', $outletId);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        } else {
            $query->whereDate('created_at', '>=', Carbon::today()->startOfMonth());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        } else {
            $query->whereDate('created_at', '<=', Carbon::today());
        }

        $groupBy = $request->get('group_by', 'day');

        if ($groupBy === 'day') {
            $data = $query->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(subtotal) as subtotal, SUM(vat_amount) as vat, SUM(discount_amount) as discount, SUM(total_amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } elseif ($groupBy === 'month') {
            $data = $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count, SUM(subtotal) as subtotal, SUM(vat_amount) as vat, SUM(discount_amount) as discount, SUM(total_amount) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        } else {
            $data = $query->selectRaw('COUNT(*) as count, SUM(subtotal) as subtotal, SUM(vat_amount) as vat, SUM(discount_amount) as discount, SUM(total_amount) as total')
                ->first();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function purchase(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $query = DB::table('purchases')
            ->where('company_id', $companyId)
            ->where('outlet_id', $outletId);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        } else {
            $query->whereDate('created_at', '>=', Carbon::today()->startOfMonth());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $data = $query->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(subtotal) as subtotal, SUM(vat) as vat, SUM(discount) as discount, SUM(total) as total, SUM(paid_amount) as paid')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $summary = DB::table('medicines')
            ->leftJoin('medicine_batches', function ($join) use ($outletId) {
                $join->on('medicine_batches.medicine_id', '=', 'medicines.id')
                    ->where('medicine_batches.outlet_id', '=', $outletId);
            })
            ->where('medicines.company_id', $companyId)
            ->where('medicines.is_active', true)
            ->selectRaw('
                COUNT(DISTINCT medicines.id) as total_medicines,
                COALESCE(SUM(medicine_batches.quantity_in_stock), 0) as total_units,
                COALESCE(SUM(medicine_batches.quantity_in_stock * medicine_batches.purchase_price_per_unit), 0) as stock_at_cost,
                COALESCE(SUM(medicine_batches.quantity_in_stock * medicine_batches.mrp_per_unit), 0) as stock_at_mrp
            ')
            ->first();

        $categoryWise = DB::table('medicines')
            ->leftJoin('medicine_batches', function ($join) use ($outletId) {
                $join->on('medicine_batches.medicine_id', '=', 'medicines.id')
                    ->where('medicine_batches.outlet_id', '=', $outletId);
            })
            ->leftJoin('medicine_categories', 'medicine_categories.id', '=', 'medicines.medicine_category_id')
            ->where('medicines.company_id', $companyId)
            ->where('medicines.is_active', true)
            ->selectRaw('
                medicine_categories.name as category,
                COUNT(DISTINCT medicines.id) as medicine_count,
                COALESCE(SUM(medicine_batches.quantity_in_stock), 0) as total_units,
                COALESCE(SUM(medicine_batches.quantity_in_stock * medicine_batches.purchase_price_per_unit), 0) as stock_value
            ')
            ->groupBy('medicine_categories.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'category_wise' => $categoryWise,
            ],
        ]);
    }

    public function expiry(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        $days = (int) $request->get('days', 90);

        $data = DB::table('medicine_batches')
            ->join('medicines', 'medicines.id', '=', 'medicine_batches.medicine_id')
            ->where('medicines.company_id', $companyId)
            ->where('medicine_batches.outlet_id', $outletId)
            ->where('medicine_batches.quantity_in_stock', '>', 0)
            ->whereDate('medicine_batches.expiry_date', '<=', Carbon::today()->addDays($days))
            ->selectRaw('
                CASE
                    WHEN DATEDIFF(medicine_batches.expiry_date, CURDATE()) <= 30 THEN "0-30 days"
                    WHEN DATEDIFF(medicine_batches.expiry_date, CURDATE()) <= 60 THEN "31-60 days"
                    WHEN DATEDIFF(medicine_batches.expiry_date, CURDATE()) <= 90 THEN "61-90 days"
                    ELSE "90+ days"
                END as expiry_group,
                COUNT(*) as batch_count,
                SUM(medicine_batches.quantity_in_stock) as total_units,
                SUM(medicine_batches.quantity_in_stock * medicine_batches.purchase_price_per_unit) as stock_value
            ')
            ->groupBy('expiry_group')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $dateFrom = $request->get('date_from', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        $sales = Sale::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw('SUM(subtotal) as revenue, SUM(vat_amount) as vat_collected, SUM(discount_amount) as discounts')
            ->first();

        $cogs = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('medicine_batches', 'medicine_batches.id', '=', 'sale_items.batch_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.outlet_id', $outletId)
            ->whereBetween('sales.created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw('SUM(sale_items.quantity * medicine_batches.purchase_price_per_unit) as cost')
            ->first();

        $grossProfit = ($sales->revenue ?? 0) - ($cogs->cost ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => ['from' => $dateFrom, 'to' => $dateTo],
                'revenue' => (float) ($sales->revenue ?? 0),
                'cost_of_goods' => (float) ($cogs->cost ?? 0),
                'gross_profit' => round($grossProfit, 2),
                'margin' => ($sales->revenue ?? 0) > 0 ? round(($grossProfit / $sales->revenue) * 100, 2) : 0,
                'vat_collected' => (float) ($sales->vat_collected ?? 0),
                'discounts_given' => (float) ($sales->discounts ?? 0),
            ],
        ]);
    }

    public function vat(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $dateFrom = $request->get('date_from', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        $salesVat = Sale::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw('SUM(total_amount - vat_amount) as taxable_sales, SUM(vat_amount) as output_vat')
            ->first();

        $purchaseVat = DB::table('purchases')
            ->where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw('SUM(subtotal) as taxable_purchases, SUM(vat) as input_vat')
            ->first();

        $outputVat = (float) ($salesVat->output_vat ?? 0);
        $inputVat = (float) ($purchaseVat->input_vat ?? 0);
        $netVat = $outputVat - $inputVat;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => ['from' => $dateFrom, 'to' => $dateTo],
                'sales' => [
                    'taxable_amount' => (float) ($salesVat->taxable_sales ?? 0),
                    'output_vat' => $outputVat,
                ],
                'purchases' => [
                    'taxable_amount' => (float) ($purchaseVat->taxable_purchases ?? 0),
                    'input_vat' => $inputVat,
                ],
                'net_vat_payable' => round($netVat, 2),
            ],
        ]);
    }

    public function narcotics(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $dateFrom = $request->get('date_from', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        $entries = DB::table('narcotics_register')
            ->join('medicines', 'medicines.id', '=', 'narcotics_register.medicine_id')
            ->where('narcotics_register.company_id', $companyId)
            ->where('narcotics_register.outlet_id', $outletId)
            ->whereBetween('narcotics_register.created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->select(
                'narcotics_register.*',
                'medicines.brand_name',
                'medicines.generic_name'
            )
            ->orderByDesc('narcotics_register.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $entries,
        ]);
    }

    public function deadStock(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        $days = (int) $request->get('days', 90);

        $deadStock = DB::table('medicines')
            ->leftJoin('sale_items', function ($join) {
                $join->on('sale_items.medicine_id', '=', 'medicines.id');
            })
            ->leftJoin('sales', function ($join) use ($outletId) {
                $join->on('sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.outlet_id', '=', $outletId);
            })
            ->leftJoin('medicine_batches', function ($join) use ($outletId) {
                $join->on('medicine_batches.medicine_id', '=', 'medicines.id')
                    ->where('medicine_batches.outlet_id', '=', $outletId);
            })
            ->where('medicines.company_id', $companyId)
            ->where('medicines.is_active', true)
            ->where(function ($q) use ($days) {
                $q->whereNull('sales.id')
                    ->orWhere('sales.created_at', '<', Carbon::today()->subDays($days));
            })
            ->select(
                'medicines.id',
                'medicines.brand_name',
                'medicines.generic_name',
                DB::raw('COALESCE(SUM(medicine_batches.quantity_in_stock), 0) as current_stock'),
                DB::raw('COALESCE(SUM(medicine_batches.quantity_in_stock * medicine_batches.purchase_price_per_unit), 0) as stock_value'),
                DB::raw('MAX(sales.created_at) as last_sold')
            )
            ->groupBy('medicines.id', 'medicines.brand_name', 'medicines.generic_name')
            ->having('current_stock', '>', 0)
            ->orderByDesc('stock_value')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deadStock,
        ]);
    }

    public function supplierDue(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $dues = DB::table('suppliers')
            ->leftJoin('purchases', function ($join) use ($companyId) {
                $join->on('purchases.supplier_id', '=', 'suppliers.id')
                    ->where('purchases.company_id', '=', $companyId);
            })
            ->where('suppliers.company_id', $companyId)
            ->where('suppliers.is_active', true)
            ->select(
                'suppliers.id',
                'suppliers.name',
                'suppliers.contact_person',
                'suppliers.phone',
                DB::raw('COALESCE(SUM(purchases.total), 0) as total_purchases'),
                DB::raw('COALESCE(SUM(purchases.paid_amount), 0) as total_paid'),
                DB::raw('COALESCE(SUM(purchases.total - purchases.paid_amount), 0) as outstanding')
            )
            ->groupBy('suppliers.id', 'suppliers.name', 'suppliers.contact_person', 'suppliers.phone')
            ->having('outstanding', '>', 0)
            ->orderByDesc('outstanding')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $dues,
        ]);
    }

    public function customerDue(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $dues = DB::table('sales')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.due_amount', '>', 0)
            ->where('customers.is_active', true)
            ->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.email',
                DB::raw('SUM(sales.due_amount) as total_dues'),
                'customers.loyalty_points'
            )
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.email', 'customers.loyalty_points')
            ->orderByDesc('total_dues')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $dues,
        ]);
    }

    public function scheduleWise(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $dateFrom = $request->get('date_from', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        $data = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('medicines', 'medicines.id', '=', 'sale_items.medicine_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.outlet_id', $outletId)
            ->whereBetween('sales.created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw('
                medicines.schedule_type,
                COUNT(DISTINCT sales.id) as sale_count,
                SUM(sale_items.quantity) as total_quantity,
                SUM(sale_items.total) as total_amount
            ')
            ->groupBy('medicines.schedule_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function categoryWise(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $dateFrom = $request->get('date_from', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        $data = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('medicines', 'medicines.id', '=', 'sale_items.medicine_id')
            ->leftJoin('medicine_categories', 'medicine_categories.id', '=', 'medicines.medicine_category_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.outlet_id', $outletId)
            ->whereBetween('sales.created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw('
                COALESCE(medicine_categories.name, "Uncategorized") as category,
                COUNT(DISTINCT sales.id) as sale_count,
                SUM(sale_items.quantity) as total_quantity,
                SUM(sale_items.total) as total_amount
            ')
            ->groupBy('medicine_categories.name')
            ->orderByDesc('total_amount')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
