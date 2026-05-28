<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomerReturn;
use App\Models\MedicineBatch;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Sales report for a date range.
     */
    public function getSalesReport(int $companyId, ?int $outletId, string $from, string $to): array
    {
        $query = Sale::where('company_id', $companyId)
            ->whereBetween('sale_date', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        $sales = $query->with(['items', 'payments.paymentMethod'])->get();

        $totalSales = $sales->sum('total_amount');
        $totalDiscount = $sales->sum('discount_amount');
        $totalVat = $sales->sum('vat_amount');
        $totalTransactions = $sales->count();
        $averageSaleValue = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        $paymentBreakdown = $sales->flatMap(fn (Sale $s) => $s->payments)
            ->groupBy(fn ($p) => $p->paymentMethod?->name ?? 'Unknown')
            ->map(fn ($payments) => $payments->sum('amount'));

        $dailySales = $sales->groupBy(fn (Sale $s) => $s->sale_date->format('Y-m-d'))
            ->map(fn ($daySales) => [
                'date' => $daySales->first()->sale_date->format('Y-m-d'),
                'total' => $daySales->sum('total_amount'),
                'transactions' => $daySales->count(),
            ])
            ->values();

        return [
            'period' => ['from' => $from, 'to' => $to],
            'total_sales' => round($totalSales, 2),
            'total_discount' => round($totalDiscount, 2),
            'total_vat' => round($totalVat, 2),
            'total_transactions' => $totalTransactions,
            'average_sale_value' => round($averageSaleValue, 2),
            'payment_breakdown' => $paymentBreakdown,
            'daily_sales' => $dailySales,
            'top_medicines' => $this->getTopSellingMedicines($sales),
        ];
    }

    /**
     * Purchase report for a date range.
     */
    public function getPurchaseReport(int $companyId, ?int $outletId, string $from, string $to): array
    {
        $query = Purchase::where('company_id', $companyId)
            ->whereBetween('purchase_date', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        $purchases = $query->with('items')->get();

        return [
            'period' => ['from' => $from, 'to' => $to],
            'total_purchases' => round($purchases->sum('total_amount'), 2),
            'total_transactions' => $purchases->count(),
            'total_paid' => round($purchases->sum('paid_amount'), 2),
            'total_due' => round($purchases->sum('total_amount') - $purchases->sum('paid_amount'), 2),
            'by_supplier' => $purchases->groupBy('supplier_id')->map(fn ($group) => [
                'supplier_id' => $group->first()->supplier_id,
                'total' => round($group->sum('total_amount'), 2),
                'count' => $group->count(),
            ])->values(),
        ];
    }

    /**
     * Inventory report for an outlet.
     */
    public function getInventoryReport(int $companyId, int $outletId): array
    {
        $batches = MedicineBatch::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->with(['medicine', 'medicine.manufacturer', 'medicine.medicineCategory'])
            ->get();

        $totalItems = $batches->count();
        $totalCostValue = $batches->sum(fn (MedicineBatch $b) => (float) $b->quantity_in_stock * (float) $b->purchase_price_per_unit);
        $totalRetailValue = $batches->sum(fn (MedicineBatch $b) => (float) $b->quantity_in_stock * (float) $b->selling_price_per_unit);

        $expiredBatches = $batches->filter(fn (MedicineBatch $b) => $b->isExpired());
        $expiringSoon = $batches->filter(fn (MedicineBatch $b) => ! $b->isExpired() && $b->daysUntilExpiry() <= 90);
        $lowStock = $batches->filter(function (MedicineBatch $b) {
            $totalMedicineStock = MedicineBatch::where('medicine_id', $b->medicine_id)
                ->where('outlet_id', $b->outlet_id)
                ->where('is_active', true)
                ->sum('quantity_in_stock');

            return $totalMedicineStock <= ($b->medicine->reorder_level ?? 10);
        });

        $byCategory = $batches->groupBy(fn (MedicineBatch $b) => $b->medicine->medicineCategory?->name ?? 'Uncategorized')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'cost_value' => round($group->sum(fn (MedicineBatch $b) => (float) $b->quantity_in_stock * (float) $b->purchase_price_per_unit), 2),
                'retail_value' => round($group->sum(fn (MedicineBatch $b) => (float) $b->quantity_in_stock * (float) $b->selling_price_per_unit), 2),
            ]);

        return [
            'total_items' => $totalItems,
            'total_cost_value' => round($totalCostValue, 2),
            'total_retail_value' => round($totalRetailValue, 2),
            'potential_profit' => round($totalRetailValue - $totalCostValue, 2),
            'expired_items' => $expiredBatches->count(),
            'expired_value' => round($expiredBatches->sum(fn (MedicineBatch $b) => (float) $b->quantity_in_stock * (float) $b->purchase_price_per_unit), 2),
            'expiring_soon' => $expiringSoon->count(),
            'low_stock_items' => $lowStock->count(),
            'by_category' => $byCategory,
        ];
    }

    /**
     * Expiry report for an outlet.
     */
    public function getExpiryReport(int $companyId, int $outletId, int $days = 90): array
    {
        $expired = MedicineBatch::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->where('expiry_date', '<=', now())
            ->with('medicine')
            ->get();

        $expiringSoon = MedicineBatch::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->with('medicine')
            ->orderBy('expiry_date', 'asc')
            ->get();

        return [
            'expired' => [
                'count' => $expired->count(),
                'value' => round($expired->sum(fn (MedicineBatch $b) => (float) $b->quantity_in_stock * (float) $b->purchase_price_per_unit), 2),
                'items' => $expired->map(fn (MedicineBatch $b) => [
                    'medicine' => $b->medicine->name,
                    'batch_number' => $b->batch_number,
                    'expiry_date' => $b->expiry_date->format('Y-m-d'),
                    'stock' => $b->quantity_in_stock,
                    'value' => round((float) $b->quantity_in_stock * (float) $b->purchase_price_per_unit, 2),
                ]),
            ],
            'expiring_soon' => [
                'count' => $expiringSoon->count(),
                'value' => round($expiringSoon->sum(fn (MedicineBatch $b) => (float) $b->quantity_in_stock * (float) $b->purchase_price_per_unit), 2),
                'items' => $expiringSoon->map(fn (MedicineBatch $b) => [
                    'medicine' => $b->medicine->name,
                    'batch_number' => $b->batch_number,
                    'expiry_date' => $b->expiry_date->format('Y-m-d'),
                    'days_left' => $b->daysUntilExpiry(),
                    'stock' => $b->quantity_in_stock,
                    'value' => round((float) $b->quantity_in_stock * (float) $b->purchase_price_per_unit, 2),
                ]),
            ],
        ];
    }

    /**
     * Profit/Loss report.
     */
    public function getProfitLossReport(int $companyId, ?int $outletId, string $from, string $to): array
    {
        $salesQuery = Sale::where('company_id', $companyId)
            ->whereBetween('sale_date', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);

        $purchaseQuery = Purchase::where('company_id', $companyId)
            ->whereBetween('purchase_date', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);

        $returnQuery = CustomerReturn::where('company_id', $companyId)
            ->whereBetween('returned_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);

        if ($outletId) {
            $salesQuery->where('outlet_id', $outletId);
            $purchaseQuery->where('outlet_id', $outletId);
            $returnQuery->where('outlet_id', $outletId);
        }

        $totalSales = $salesQuery->sum('total_amount');
        $totalPurchases = $purchaseQuery->sum('total_amount');
        $totalReturns = $returnQuery->sum('refund_amount');
        $totalVatCollected = $salesQuery->sum('vat_amount');
        $totalDiscountGiven = $salesQuery->sum('discount_amount');

        // Calculate COGS from sale items
        $saleIds = Sale::where('company_id', $companyId)
            ->whereBetween('sale_date', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->pluck('id');

        $cogs = SaleItem::whereIn('sale_id', $saleIds)
            ->join('medicine_batches', 'sale_items.batch_id', '=', 'medicine_batches.id')
            ->sum(DB::raw('sale_items.quantity * medicine_batches.purchase_price_per_unit'));

        $grossProfit = $totalSales - $cogs - $totalReturns;
        $netProfit = $grossProfit - $totalDiscountGiven;

        return [
            'period' => ['from' => $from, 'to' => $to],
            'revenue' => [
                'total_sales' => round($totalSales, 2),
                'total_returns' => round($totalReturns, 2),
                'net_sales' => round($totalSales - $totalReturns, 2),
            ],
            'cost_of_goods_sold' => round($cogs, 2),
            'gross_profit' => round($grossProfit, 2),
            'discounts' => round($totalDiscountGiven, 2),
            'net_profit' => round($netProfit, 2),
            'profit_margin' => $totalSales > 0 ? round(($netProfit / $totalSales) * 100, 2) : 0,
            'vat_collected' => round($totalVatCollected, 2),
        ];
    }

    /**
     * VAT report.
     */
    public function getVatReport(int $companyId, ?int $outletId, string $from, string $to): array
    {
        $query = SaleItem::whereHas('sale', function ($q) use ($companyId, $outletId, $from, $to) {
            $q->where('company_id', $companyId)
                ->whereBetween('sale_date', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);
            if ($outletId) {
                $q->where('outlet_id', $outletId);
            }
        });

        $totalTaxable = $query->sum('taxable_amount');
        $totalVat = $query->sum('vat_amount');

        $purchaseQuery = Purchase::where('company_id', $companyId)
            ->whereBetween('purchase_date', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);

        if ($outletId) {
            $purchaseQuery->where('outlet_id', $outletId);
        }

        $inputVat = $purchaseQuery->sum('vat_amount');

        return [
            'period' => ['from' => $from, 'to' => $to],
            'output_vat' => [
                'taxable_amount' => round($totalTaxable, 2),
                'vat_amount' => round($totalVat, 2),
            ],
            'input_vat' => round($inputVat, 2),
            'net_vat_payable' => round($totalVat - $inputVat, 2),
        ];
    }

    /**
     * Get top selling medicines from a collection of sales.
     */
    private function getTopSellingMedicines(Collection $sales, int $limit = 10): Collection
    {
        return $sales->flatMap(fn (Sale $s) => $s->items)
            ->groupBy('medicine_id')
            ->map(fn ($items) => [
                'medicine_id' => $items->first()->medicine_id,
                'medicine_name' => $items->first()->medicine_name,
                'quantity_sold' => $items->sum('quantity'),
                'total_revenue' => $items->sum('total_amount'),
            ])
            ->sortByDesc('total_revenue')
            ->take($limit)
            ->values();
    }
}
