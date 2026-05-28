<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::where('company_id', $request->user()->company_id)
            ->where('outlet_id', $request->user()->outlet_id)
            ->with(['customer:id,name,phone', 'payments', 'dispensedBy:id,name']);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('sale_type')) {
            $query->where('sale_type', $request->sale_type);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $sales = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $sales,
        ]);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $companyId = $user->company_id;
            $outletId = $user->outlet_id;

            // Generate invoice number using MAX to avoid race condition
            $dateStr = Carbon::now()->format('Ymd');
            $lastInvoice = Sale::where('company_id', $companyId)
                ->whereDate('created_at', Carbon::today())
                ->lockForUpdate()
                ->max('invoice_number');

            $sequence = 1;
            if ($lastInvoice) {
                $lastSequence = (int) substr($lastInvoice, -4);
                $sequence = $lastSequence + 1;
            }
            $sequenceStr = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = "INV-{$companyId}-{$dateStr}-{$sequenceStr}";

            // Process items and calculate totals
            $subtotal = 0;
            $vatAmount = 0;
            $totalDiscount = 0;
            $saleItems = [];
            $batchesUsed = [];
            $prescriptionRequired = false;

            foreach ($request->items as $item) {
                $medicine = Medicine::where('id', $item['medicine_id'])
                    ->where('company_id', $companyId)
                    ->firstOrFail();

                // Check if prescription required for Schedule H/H1/X
                if (in_array($medicine->schedule_type, ['h', 'h1', 'x'])) {
                    $prescriptionRequired = true;
                    if (empty($request->prescription_id)) {
                        throw new \Exception("Prescription is required for {$medicine->brand_name} (Schedule {$medicine->schedule_type}).");
                    }
                }

                // Get batch - either specified or FIFO
                if (! empty($item['batch_id'])) {
                    $batch = DB::table('medicine_batches')
                        ->where('id', $item['batch_id'])
                        ->where('medicine_id', $medicine->id)
                        ->where('outlet_id', $outletId)
                        ->whereDate('expiry_date', '>', now())
                        ->lockForUpdate()
                        ->first();

                    if (! $batch) {
                        throw new \Exception("Batch not found or expired for {$medicine->brand_name}.");
                    }

                    if ($batch->quantity_in_stock < $item['quantity']) {
                        throw new \Exception("Insufficient stock for batch {$batch->batch_number} of {$medicine->brand_name}. Available: {$batch->quantity_in_stock}");
                    }

                    $batchesUsed[] = ['batch' => $batch, 'quantity' => $item['quantity']];
                } else {
                    // FIFO: Get earliest expiring batch with sufficient stock
                    $batch = DB::table('medicine_batches')
                        ->where('medicine_id', $medicine->id)
                        ->where('outlet_id', $outletId)
                        ->where('quantity_in_stock', '>=', $item['quantity'])
                        ->whereDate('expiry_date', '>', now())
                        ->orderBy('expiry_date')
                        ->lockForUpdate()
                        ->first();

                    if (! $batch) {
                        // Try to accumulate from multiple batches
                        $batches = DB::table('medicine_batches')
                            ->where('medicine_id', $medicine->id)
                            ->where('outlet_id', $outletId)
                            ->where('quantity_in_stock', '>', 0)
                            ->whereDate('expiry_date', '>', now())
                            ->orderBy('expiry_date')
                            ->lockForUpdate()
                            ->get();

                        $totalAvailable = $batches->sum('quantity_in_stock');
                        if ($totalAvailable < $item['quantity']) {
                            throw new \Exception("Insufficient stock for {$medicine->brand_name}. Available: {$totalAvailable}");
                        }

                        // Accumulate from multiple batches (FIFO)
                        $remaining = (float) $item['quantity'];
                        foreach ($batches as $b) {
                            if ($remaining <= 0) break;
                            $take = min((float) $b->quantity_in_stock, $remaining);
                            $batchesUsed[] = ['batch' => $b, 'quantity' => $take];
                            $remaining -= $take;
                        }

                        $batch = $batches->first();
                    } else {
                        $batchesUsed[] = ['batch' => $batch, 'quantity' => $item['quantity']];
                    }
                }

                $unitPrice = $item['unit_price'] ?? (float) $batch->selling_price_per_unit;
                $quantity = (float) $item['quantity'];
                $itemDiscount = (float) ($item['discount'] ?? 0);
                $lineTotal = ($unitPrice * $quantity) - $itemDiscount;

                // Calculate VAT per item
                $itemVat = 0;
                $vatRate = 13;
                $medicine->loadMissing('saltComposition');
                $itemVat = ($lineTotal * $vatRate) / 100;

                $subtotal += $lineTotal;
                $vatAmount += $itemVat;
                $totalDiscount += $itemDiscount;

                $saleItems[] = [
                    'medicine_id' => $medicine->id,
                    'batch_id' => $batch->id,
                    'quantity' => $quantity,
                    'unit_type' => $medicine->unit_type ?? 'strip',
                    'mrp' => (float) $batch->mrp_per_unit,
                    'selling_price' => $unitPrice,
                    'discount' => $itemDiscount,
                    'vat' => round($itemVat, 2),
                    'total' => round($lineTotal + $itemVat, 2),
                    'prescription_required' => $prescriptionRequired,
                ];
            }

            // Deduct stock from all batches used
            foreach ($batchesUsed as $used) {
                DB::table('medicine_batches')
                    ->where('id', $used['batch']->id)
                    ->decrement('quantity_in_stock', $used['quantity']);
            }

            // Apply bill-level discount
            $billDiscount = (float) ($request->discount ?? 0);
            $totalDiscount += $billDiscount;

            $grandTotal = $subtotal + $vatAmount - $billDiscount;
            $paidAmount = (float) ($request->paid_amount ?? $grandTotal);
            $dueAmount = max(0, $grandTotal - $paidAmount);

            // Create sale
            $sale = Sale::create([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'register_id' => $request->register_id,
                'customer_id' => $request->customer_id,
                'prescription_id' => $request->prescription_id,
                'invoice_number' => $invoiceNumber,
                'sale_type' => $request->sale_type ?? 'walk_in',
                'subtotal' => round($subtotal, 2),
                'discount_amount' => round($totalDiscount, 2),
                'discount_type' => $request->discount_type ?? 'fixed',
                'vat_amount' => round($vatAmount, 2),
                'vat_percentage' => 13,
                'total_amount' => round($grandTotal, 2),
                'paid_amount' => round($paidAmount, 2),
                'due_amount' => round($dueAmount, 2),
                'payment_status' => $dueAmount > 0 ? ($paidAmount > 0 ? 'partial' : 'due') : 'paid',
                'dispensed_by' => $user->id,
                'notes' => $request->notes,
            ]);

            // Create sale items
            foreach ($saleItems as &$saleItem) {
                $saleItem['sale_id'] = $sale->id;
            }
            DB::table('sale_items')->insert($saleItems);

            // Process payments
            $payments = $request->payments ?? [];

            if (empty($payments)) {
                DB::table('sale_payments')->insert([
                    'sale_id' => $sale->id,
                    'payment_method_id' => $request->payment_method_id,
                    'amount' => round($paidAmount, 2),
                    'reference_number' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                foreach ($payments as $payment) {
                    DB::table('sale_payments')->insert([
                        'sale_id' => $sale->id,
                        'payment_method_id' => $payment['payment_method_id'],
                        'amount' => (float) $payment['amount'],
                        'reference_number' => $payment['reference_number'] ?? null,
                        'gateway_response' => isset($payment['gateway_response']) ? json_encode($payment['gateway_response']) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Update customer loyalty points
            if ($request->customer_id) {
                $customer = Customer::where('id', $request->customer_id)
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->first();

                if ($customer) {
                    $loyaltyPoints = (int) floor($grandTotal / 100);
                    $customer->increment('loyalty_points', $loyaltyPoints);
                }
            }

            // Reload with relationships
            $sale->load(['customer', 'items.medicine', 'items.batch', 'payments.paymentMethod', 'dispensedBy:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully.',
                'data' => [
                    'sale' => $sale,
                    'invoice_number' => $invoiceNumber,
                ],
            ], 201);
        });
    }

    public function show(Request $request, Sale $sale): JsonResponse
    {
        if ($sale->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $sale->load([
            'customer',
            'items.medicine',
            'items.batch',
            'payments.paymentMethod',
            'dispensedBy:id,name',
            'prescription',
        ]);

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    public function invoice(Request $request, Sale $sale): JsonResponse
    {
        if ($sale->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $sale->load([
            'company',
            'outlet',
            'customer',
            'items.medicine',
            'payments.paymentMethod',
            'dispensedBy:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'sale' => $sale,
                'company' => $sale->company,
                'outlet' => $sale->outlet,
                'customer' => $sale->customer,
                'items' => $sale->items,
                'payments' => $sale->payments,
                'cashier' => $sale->dispensedBy,
            ],
        ]);
    }

    public function dailySummary(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));

        $summary = Sale::where('company_id', $companyId)
            ->where('outlet_id', $outletId)
            ->whereDate('created_at', $date)
            ->selectRaw('
                COUNT(*) as total_sales,
                COALESCE(SUM(subtotal), 0) as subtotal,
                COALESCE(SUM(vat_amount), 0) as vat_total,
                COALESCE(SUM(discount_amount), 0) as discount_total,
                COALESCE(SUM(total_amount), 0) as grand_total
            ')
            ->first();

        $paymentBreakdown = DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('payment_methods', 'payment_methods.id', '=', 'sale_payments.payment_method_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.outlet_id', $outletId)
            ->whereDate('sales.created_at', $date)
            ->select(
                'payment_methods.name',
                DB::raw('SUM(sale_payments.amount) as total')
            )
            ->groupBy('payment_methods.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'summary' => $summary,
                'payment_breakdown' => $paymentBreakdown,
            ],
        ]);
    }
}
