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
    /**
     * Shared response shape for all report pages: { headers, rows, totals }.
     * Dates are accepted as either `from`/`to` (frontend) or legacy
     * `date_from`/`date_to`.
     */
    private function table(string $title, array $headers, array $rows, array $totals = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'title' => $title,
                'headers' => $headers,
                'rows' => $rows,
                'totals' => $totals,
            ],
        ]);
    }

    private function dateRange(Request $request): array
    {
        $from = $request->get('from', $request->get('date_from', Carbon::today()->startOfMonth()->format('Y-m-d')));
        $to = $request->get('to', $request->get('date_to', Carbon::today()->format('Y-m-d')));

        return [$from, $to];
    }

    public function sales(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        [$from, $to] = $this->dateRange($request);

        $query = Sale::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $groupBy = $request->get('group_by', 'day');

        $select = 'DATE(created_at) as period, COUNT(*) as count, SUM(subtotal) as subtotal, SUM(vat_amount) as vat, SUM(discount_amount) as discount, SUM(total_amount) as total';

        if ($groupBy === 'month') {
            $select = 'DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count, SUM(subtotal) as subtotal, SUM(vat_amount) as vat, SUM(discount_amount) as discount, SUM(total_amount) as total';
        } elseif ($groupBy === 'week') {
            $select = 'DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(DATE(created_at)) DAY) as period, COUNT(*) as count, SUM(subtotal) as subtotal, SUM(vat_amount) as vat, SUM(discount_amount) as discount, SUM(total_amount) as total';
        }

        $data = $query->selectRaw($select)
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $headers = ['Period', 'Invoices', 'Subtotal', 'VAT', 'Discount', 'Total'];

        $rows = $data->map(fn ($row) => [
            $row->period,
            (int) $row->count,
            (float) $row->subtotal,
            (float) $row->vat,
            (float) $row->discount,
            (float) $row->total,
        ])->all();

        $totals = [
            'Invoices' => (int) $data->sum('count'),
            'Subtotal' => round((float) $data->sum('subtotal'), 2),
            'VAT' => round((float) $data->sum('vat'), 2),
            'Discount' => round((float) $data->sum('discount'), 2),
            'Total' => round((float) $data->sum('total'), 2),
        ];

        return $this->table('Sales Report', $headers, $rows, $totals);
    }

    public function purchase(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        [$from, $to] = $this->dateRange($request);

        $query = DB::table('purchases')
            ->where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $data = $query->selectRaw('DATE(created_at) as period, COUNT(*) as count, SUM(subtotal) as subtotal, SUM(vat) as vat, SUM(discount) as discount, SUM(total) as total, SUM(paid_amount) as paid')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $headers = ['Period', 'Purchases', 'Subtotal', 'VAT', 'Discount', 'Total', 'Paid'];

        $rows = $data->map(fn ($row) => [
            $row->period,
            (int) $row->count,
            (float) $row->subtotal,
            (float) $row->vat,
            (float) $row->discount,
            (float) $row->total,
            (float) $row->paid,
        ])->all();

        $totals = [
            'Purchases' => (int) $data->sum('count'),
            'Subtotal' => round((float) $data->sum('subtotal'), 2),
            'VAT' => round((float) $data->sum('vat'), 2),
            'Discount' => round((float) $data->sum('discount'), 2),
            'Total' => round((float) $data->sum('total'), 2),
            'Paid' => round((float) $data->sum('paid'), 2),
        ];

        return $this->table('Purchase Report', $headers, $rows, $totals);
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
                COALESCE(medicine_categories.name, "Uncategorized") as category,
                COUNT(DISTINCT medicines.id) as medicine_count,
                COALESCE(SUM(medicine_batches.quantity_in_stock), 0) as total_units,
                COALESCE(SUM(medicine_batches.quantity_in_stock * medicine_batches.purchase_price_per_unit), 0) as stock_value
            ')
            ->groupBy('category')
            ->orderByDesc('stock_value')
            ->get();

        $headers = ['Category', 'Medicines', 'Units', 'Stock Value (Cost)'];

        $rows = $categoryWise->map(fn ($row) => [
            $row->category,
            (int) $row->medicine_count,
            (float) $row->total_units,
            round((float) $row->stock_value, 2),
        ])->all();

        $totals = [
            'Medicines' => (int) $summary->total_medicines,
            'Units' => (float) $summary->total_units,
            'Stock Value (Cost)' => round((float) $summary->stock_at_cost, 2),
        ];

        return $this->table('Inventory Report', $headers, $rows, $totals);
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
                    WHEN DATEDIFF(medicine_batches.expiry_date, CURDATE()) <= 0 THEN "Expired"
                    WHEN DATEDIFF(medicine_batches.expiry_date, CURDATE()) <= 30 THEN "0-30 days"
                    WHEN DATEDIFF(medicine_batches.expiry_date, CURDATE()) <= 60 THEN "31-60 days"
                    ELSE "61+ days"
                END as expiry_group,
                COUNT(*) as batch_count,
                SUM(medicine_batches.quantity_in_stock) as total_units,
                SUM(medicine_batches.quantity_in_stock * medicine_batches.purchase_price_per_unit) as stock_value
            ')
            ->groupBy('expiry_group')
            ->get();

        $headers = ['Expiry Group', 'Batches', 'Units', 'Stock Value'];

        $rows = $data->map(fn ($row) => [
            $row->expiry_group,
            (int) $row->batch_count,
            (float) $row->total_units,
            round((float) $row->stock_value, 2),
        ])->all();

        $totals = [
            'Batches' => (int) $data->sum('batch_count'),
            'Units' => (float) $data->sum('total_units'),
            'Stock Value' => round((float) $data->sum('stock_value'), 2),
        ];

        return $this->table('Expiry Report', $headers, $rows, $totals);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        [$from, $to] = $this->dateRange($request);

        $sales = Sale::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->whereBetween('created_at', [$from, $to.' 23:59:59'])
            ->selectRaw('SUM(subtotal) as revenue, SUM(vat_amount) as vat_collected, SUM(discount_amount) as discounts')
            ->first();

        $cogs = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('medicine_batches', 'medicine_batches.id', '=', 'sale_items.batch_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.outlet_id', $outletId)
            ->whereBetween('sales.created_at', [$from, $to.' 23:59:59'])
            ->selectRaw('SUM(sale_items.quantity * medicine_batches.purchase_price_per_unit) as cost')
            ->first();

        $revenue = (float) ($sales->revenue ?? 0);
        $cost = (float) ($cogs->cost ?? 0);
        $grossProfit = $revenue - $cost;
        $margin = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0;

        $rows = [
            ['Revenue', round($revenue, 2)],
            ['Cost of Goods', round($cost, 2)],
            ['Gross Profit', round($grossProfit, 2)],
            ['Margin %', $margin],
            ['VAT Collected', round((float) ($sales->vat_collected ?? 0), 2)],
            ['Discounts Given', round((float) ($sales->discounts ?? 0), 2)],
        ];

        return $this->table(
            'Profit & Loss',
            ['Metric', 'Value'],
            $rows,
            ['Gross Profit' => round($grossProfit, 2), 'Margin %' => $margin]
        );
    }

    public function vat(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        [$from, $to] = $this->dateRange($request);

        $salesVat = Sale::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->whereBetween('created_at', [$from, $to.' 23:59:59'])
            ->selectRaw('SUM(total_amount - vat_amount) as taxable_sales, SUM(vat_amount) as output_vat')
            ->first();

        $purchaseVat = DB::table('purchases')
            ->where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->whereBetween('created_at', [$from, $to.' 23:59:59'])
            ->selectRaw('SUM(subtotal) as taxable_purchases, SUM(vat) as input_vat')
            ->first();

        $outputVat = (float) ($salesVat->output_vat ?? 0);
        $inputVat = (float) ($purchaseVat->input_vat ?? 0);
        $netVat = $outputVat - $inputVat;

        $rows = [
            ['Sales', round((float) ($salesVat->taxable_sales ?? 0), 2), round($outputVat, 2)],
            ['Purchases', round((float) ($purchaseVat->taxable_purchases ?? 0), 2), round($inputVat, 2)],
            ['Net VAT Payable', round($netVat, 2), 0],
        ];

        return $this->table(
            'VAT Report',
            ['Item', 'Taxable Amount', 'VAT'],
            $rows,
            ['Net VAT Payable' => round($netVat, 2)]
        );
    }

    public function narcotics(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        [$from, $to] = $this->dateRange($request);

        $entries = DB::table('narcotics_register')
            ->join('medicines', 'medicines.id', '=', 'narcotics_register.medicine_id')
            ->leftJoin('users', 'users.id', '=', 'narcotics_register.dispensed_by')
            ->where('narcotics_register.company_id', $companyId)
            ->where('narcotics_register.outlet_id', $outletId)
            ->whereBetween('narcotics_register.created_at', [$from, $to.' 23:59:59'])
            ->select(
                'narcotics_register.created_at',
                'narcotics_register.patient_name',
                'narcotics_register.patient_address',
                'narcotics_register.doctor_name',
                'narcotics_register.prescription_number',
                'narcotics_register.quantity',
                'narcotics_register.balance',
                'medicines.brand_name',
                'users.name as dispensed_by_name'
            )
            ->orderByDesc('narcotics_register.created_at')
            ->get();

        $headers = ['Date', 'Patient', 'Medicine', 'Doctor', 'Prescription #', 'Quantity', 'Balance', 'Dispensed By'];

        $rows = $entries->map(fn ($row) => [
            $row->created_at,
            $row->patient_name,
            $row->brand_name,
            $row->doctor_name,
            $row->prescription_number,
            (float) $row->quantity,
            (float) $row->balance,
            $row->dispensed_by_name,
        ])->all();

        return $this->table('Narcotics Register', $headers, $rows, [
            'Quantity' => round((float) $entries->sum('quantity'), 2),
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

        $headers = ['Medicine', 'Generic Name', 'Stock', 'Stock Value', 'Last Sold'];

        $rows = $deadStock->map(fn ($row) => [
            $row->brand_name,
            $row->generic_name,
            (float) $row->current_stock,
            round((float) $row->stock_value, 2),
            $row->last_sold,
        ])->all();

        return $this->table('Dead Stock Report', $headers, $rows, [
            'Stock' => (float) $deadStock->sum('current_stock'),
            'Stock Value' => round((float) $deadStock->sum('stock_value'), 2),
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

        $headers = ['Supplier', 'Contact Person', 'Phone', 'Total Purchases', 'Paid', 'Outstanding'];

        $rows = $dues->map(fn ($row) => [
            $row->name,
            $row->contact_person,
            $row->phone,
            round((float) $row->total_purchases, 2),
            round((float) $row->total_paid, 2),
            round((float) $row->outstanding, 2),
        ])->all();

        return $this->table('Supplier Due Report', $headers, $rows, [
            'Outstanding' => round((float) $dues->sum('outstanding'), 2),
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

        $headers = ['Customer', 'Phone', 'Email', 'Total Dues', 'Loyalty Points'];

        $rows = $dues->map(fn ($row) => [
            $row->name,
            $row->phone,
            $row->email,
            round((float) $row->total_dues, 2),
            (int) $row->loyalty_points,
        ])->all();

        return $this->table('Customer Due Report', $headers, $rows, [
            'Total Dues' => round((float) $dues->sum('total_dues'), 2),
        ]);
    }

    public function scheduleWise(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        [$from, $to] = $this->dateRange($request);

        $data = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('medicines', 'medicines.id', '=', 'sale_items.medicine_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.outlet_id', $outletId)
            ->whereBetween('sales.created_at', [$from, $to.' 23:59:59'])
            ->selectRaw('
                medicines.schedule_type,
                COUNT(DISTINCT sales.id) as sale_count,
                SUM(sale_items.quantity) as total_quantity,
                SUM(sale_items.total) as total_amount
            ')
            ->groupBy('medicines.schedule_type')
            ->get();

        $headers = ['Schedule', 'Sales', 'Quantity', 'Amount'];

        $rows = $data->map(fn ($row) => [
            strtoupper((string) $row->schedule_type),
            (int) $row->sale_count,
            (float) $row->total_quantity,
            round((float) $row->total_amount, 2),
        ])->all();

        return $this->table('Schedule-wise Sales', $headers, $rows, [
            'Amount' => round((float) $data->sum('total_amount'), 2),
        ]);
    }

    public function categoryWise(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        [$from, $to] = $this->dateRange($request);

        $data = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('medicines', 'medicines.id', '=', 'sale_items.medicine_id')
            ->leftJoin('medicine_categories', 'medicine_categories.id', '=', 'medicines.medicine_category_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.outlet_id', $outletId)
            ->whereBetween('sales.created_at', [$from, $to.' 23:59:59'])
            ->selectRaw('
                COALESCE(medicine_categories.name, "Uncategorized") as category,
                COUNT(DISTINCT sales.id) as sale_count,
                SUM(sale_items.quantity) as total_quantity,
                SUM(sale_items.total) as total_amount
            ')
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->get();

        $headers = ['Category', 'Sales', 'Quantity', 'Amount'];

        $rows = $data->map(fn ($row) => [
            $row->category,
            (int) $row->sale_count,
            (float) $row->total_quantity,
            round((float) $row->total_amount, 2),
        ])->all();

        return $this->table('Category-wise Sales', $headers, $rows, [
            'Amount' => round((float) $data->sum('total_amount'), 2),
        ]);
    }
}
