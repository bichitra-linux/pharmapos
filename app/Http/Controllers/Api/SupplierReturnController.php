<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SupplierReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('supplier_returns')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_returns.supplier_id')
            ->where('supplier_returns.company_id', $request->user()->company_id)
            ->select(
                'supplier_returns.*',
                'suppliers.name as supplier_name'
            );

        if ($request->filled('supplier_id')) {
            $query->where('supplier_returns.supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('supplier_returns.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('supplier_returns.created_at', '<=', $request->date_to);
        }

        $returns = $query->orderByDesc('supplier_returns.created_at')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $returns,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.batch_id' => 'required|exists:medicine_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $companyId = $request->user()->company_id;
            $outletId = $request->user()->outlet_id;
            $totalAmount = 0;

            // Generate return number with lock to prevent race conditions
            $dateStr = now()->format('Ymd');
            $prefix = "SRET-{$companyId}-{$dateStr}-";
            $lastReturn = DB::table('supplier_returns')
                ->where('company_id', $companyId)
                ->where('return_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->max('return_number');

            $sequence = 1;
            if ($lastReturn) {
                $lastSequence = (int) substr($lastReturn, -4);
                $sequence = $lastSequence >= 9999 ? 1 : $lastSequence + 1;
            }
            $returnNumber = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            $returnId = DB::table('supplier_returns')->insertGetId([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'supplier_id' => $request->supplier_id,
                'purchase_id' => $request->purchase_id,
                'return_number' => $returnNumber,
                'return_date' => now(),
                'total_amount' => 0,
                'refund_status' => 'received',
                'reason' => $request->notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Batch-fetch and lock batches for stock updates
            $batchIds = collect($request->items)->pluck('batch_id')->unique();
            $batches = DB::table('medicine_batches')
                ->where('outlet_id', $outletId)
                ->whereIn('id', $batchIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($request->items as $item) {
                $batch = $batches->get($item['batch_id']);

                if (! $batch || $batch->quantity_in_stock < $item['quantity']) {
                    throw new \Exception('Insufficient stock in batch for return.');
                }

                $itemTotal = $batch->purchase_price_per_unit * $item['quantity'];
                $totalAmount += $itemTotal;

                DB::table('supplier_return_items')->insert([
                    'supplier_return_id' => $returnId,
                    'medicine_id' => $item['medicine_id'],
                    'batch_id' => $item['batch_id'],
                    'quantity' => $item['quantity'],
                    'amount' => $itemTotal,
                    'reason' => $item['reason'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Deduct stock
                DB::table('medicine_batches')
                    ->where('id', $item['batch_id'])
                    ->decrement('quantity_in_stock', $item['quantity']);
            }

            DB::table('supplier_returns')
                ->where('id', $returnId)
                ->update(['total_amount' => $totalAmount]);

            $return = DB::table('supplier_returns')->where('id', $returnId)->first();

            return response()->json([
                'success' => true,
                'message' => 'Supplier return processed successfully.',
                'data' => $return,
            ], 201);
        });
    }
}
