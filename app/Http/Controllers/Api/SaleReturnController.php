<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\CustomerReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SaleReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CustomerReturn::where('company_id', $request->user()->company_id)
            ->where('outlet_id', $request->user()->outlet_id)
            ->with(['sale:id,invoice_number,customer_id', 'sale.customer:id,name,phone', 'processedBy:id,name']);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $returns = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $returns,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.batch_id' => 'required|exists:medicine_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.reason' => 'nullable|string|max:255',
            'refund_amount' => 'required|numeric|min:0',
            'refund_method' => 'nullable|string|max:50',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $companyId = $user->company_id;
            $outletId = $user->outlet_id;

            $sale = Sale::where('id', $request->sale_id)
                ->where('company_id', $companyId)
                ->with('items')
                ->firstOrFail();

            // Generate return number with lock to prevent race conditions
            $dateStr = now()->format('Ymd');
            $prefix = "RET-{$companyId}-{$dateStr}-";
            $lastReturn = CustomerReturn::where('company_id', $companyId)
                ->where('return_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->max('return_number');

            $sequence = 1;
            if ($lastReturn) {
                $lastSequence = (int) substr($lastReturn, -4);
                $sequence = $lastSequence >= 9999 ? 1 : $lastSequence + 1;
            }
            $returnNumber = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            $return = CustomerReturn::create([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'sale_id' => $sale->id,
                'processed_by' => $user->id,
                'return_number' => $returnNumber,
                'return_date' => now(),
                'total_amount' => $request->refund_amount,
                'refund_amount' => $request->refund_amount,
                'refund_method' => $request->refund_method ?? 'cash',
                'reason' => $request->reason,
            ]);

            // Batch-fetch and lock batches for stock updates
            $batchIds = collect($request->items)->pluck('batch_id')->unique();
            $batches = DB::table('medicine_batches')
                ->whereIn('id', $batchIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Pre-fetch all already-returned quantities for this sale (one query, not N)
            $alreadyReturnedByItem = DB::table('customer_return_items')
                ->join('customer_returns', 'customer_return_items.return_id', '=', 'customer_returns.id')
                ->where('customer_returns.sale_id', $sale->id)
                ->whereIn('customer_return_items.batch_id', $batchIds)
                ->groupBy('customer_return_items.medicine_id', 'customer_return_items.batch_id')
                ->selectRaw('customer_return_items.medicine_id, customer_return_items.batch_id, SUM(customer_return_items.quantity) as total_returned')
                ->get()
                ->keyBy(fn ($row) => $row->medicine_id.'-'.$row->batch_id)
                ->map(fn ($row) => (float) $row->total_returned);

            foreach ($request->items as $item) {
                $saleItem = $sale->items->first(function ($si) use ($item) {
                    return $si->medicine_id == $item['medicine_id'] && $si->batch_id == $item['batch_id'];
                });
                if (! $saleItem) {
                    throw new \Exception("Sale item not found for medicine {$item['medicine_id']} batch {$item['batch_id']}.");
                }

                $key = $item['medicine_id'].'-'.$item['batch_id'];
                $alreadyReturned = (float) ($alreadyReturnedByItem[$key] ?? 0);

                $maxReturnable = $saleItem->quantity - $alreadyReturned;
                if ($item['quantity'] > $maxReturnable) {
                    throw new \Exception(
                        "Return quantity ({$item['quantity']}) exceeds returnable quantity ({$maxReturnable})."
                    );
                }

                DB::table('customer_return_items')->insert([
                    'return_id' => $return->id,
                    'medicine_id' => $item['medicine_id'],
                    'batch_id' => $item['batch_id'],
                    'quantity' => $item['quantity'],
                    'amount' => $saleItem->selling_price * $item['quantity'],
                    'reason' => $item['reason'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Restore batch stock
                DB::table('medicine_batches')
                    ->where('id', $item['batch_id'])
                    ->increment('quantity_in_stock', $item['quantity']);
            }

            // Update sale payment status if fully returned
            $totalReturned = CustomerReturn::where('sale_id', $sale->id)
                ->sum('refund_amount');

            if ($totalReturned >= $sale->total_amount) {
                $sale->update(['payment_status' => 'refunded']);
            }

            $return->load(['sale', 'customer', 'items.medicine']);

            return response()->json([
                'success' => true,
                'message' => 'Return processed successfully.',
                'data' => $return,
            ], 201);
        });
    }
}
