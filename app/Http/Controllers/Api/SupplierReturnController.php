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

            $returnId = DB::table('supplier_returns')->insertGetId([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'supplier_id' => $request->supplier_id,
                'purchase_id' => $request->purchase_id,
                'user_id' => $request->user()->id,
                'return_number' => 'SRET-'.$companyId.'-'.now()->format('YmdHis'),
                'total_amount' => 0,
                'status' => 'completed',
                'notes' => $request->notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($request->items as $item) {
                $batch = DB::table('medicine_batches')
                    ->where('id', $item['batch_id'])
                    ->where('outlet_id', $outletId)
                    ->first();

                if (! $batch || $batch->quantity < $item['quantity']) {
                    throw new \Exception('Insufficient stock in batch for return.');
                }

                $itemTotal = $batch->purchase_price * $item['quantity'];
                $totalAmount += $itemTotal;

                DB::table('supplier_return_items')->insert([
                    'supplier_return_id' => $returnId,
                    'medicine_id' => $item['medicine_id'],
                    'batch_id' => $item['batch_id'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $batch->purchase_price,
                    'total' => $itemTotal,
                    'reason' => $item['reason'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Deduct stock
                DB::table('medicine_batches')
                    ->where('id', $item['batch_id'])
                    ->decrement('quantity', $item['quantity']);
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
