<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SaleReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SaleReturn::where('company_id', $request->user()->company_id)
            ->where('outlet_id', $request->user()->outlet_id)
            ->with(['sale:id,invoice_number', 'customer:id,name,phone', 'user:id,name']);

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
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.reason' => 'nullable|string|max:255',
            'refund_amount' => 'required|numeric|min:0',
            'refund_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $companyId = $user->company_id;
            $outletId = $user->outlet_id;

            $sale = Sale::where('id', $request->sale_id)
                ->where('company_id', $companyId)
                ->with('items')
                ->firstOrFail();

            $returnNumber = 'RET-'.$companyId.'-'.now()->format('YmdHis');

            $return = SaleReturn::create([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'user_id' => $user->id,
                'return_number' => $returnNumber,
                'refund_amount' => $request->refund_amount,
                'refund_method' => $request->refund_method ?? 'cash',
                'reason' => $request->notes,
                'status' => 'completed',
            ]);

            foreach ($request->items as $item) {
                $saleItem = $sale->items->firstWhere('id', $item['sale_item_id']);
                if (! $saleItem) {
                    throw new \Exception("Sale item {$item['sale_item_id']} not found.");
                }

                if ($item['quantity'] > $saleItem->quantity) {
                    throw new \Exception("Return quantity exceeds original quantity for item {$saleItem->id}.");
                }

                DB::table('sale_return_items')->insert([
                    'sale_return_id' => $return->id,
                    'sale_item_id' => $saleItem->id,
                    'medicine_id' => $saleItem->medicine_id,
                    'batch_id' => $saleItem->batch_id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $saleItem->unit_price,
                    'total' => $saleItem->unit_price * $item['quantity'],
                    'reason' => $item['reason'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Restore batch stock
                DB::table('medicine_batches')
                    ->where('id', $saleItem->batch_id)
                    ->increment('quantity', $item['quantity']);
            }

            // Update sale payment status if fully returned
            $totalReturned = SaleReturn::where('sale_id', $sale->id)
                ->where('status', 'completed')
                ->sum('refund_amount');

            if ($totalReturned >= $sale->total) {
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
