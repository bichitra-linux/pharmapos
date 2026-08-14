<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\MedicineBatch;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class InventoryController extends Controller
{
    public function stock(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $query = MedicineBatch::where('medicine_batches.company_id', $companyId)
            ->where('medicine_batches.outlet_id', $outletId)
            ->where('medicine_batches.quantity_in_stock', '>', 0)
            ->with(['medicine', 'medicine.manufacturer', 'medicine.medicineCategory'])
            ->select('medicine_batches.*');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('medicine', function ($q) use ($search) {
                $q->where('brand_name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('medicine', function ($q) use ($request) {
                $q->where('medicine_category_id', $request->category_id);
            });
        }

        if ($request->filled('manufacturer_id')) {
            $query->whereHas('medicine', function ($q) use ($request) {
                $q->where('manufacturer_id', $request->manufacturer_id);
            });
        }

        if ($request->filled('schedule')) {
            $query->whereHas('medicine', function ($q) use ($request) {
                $q->where('schedule_type', $request->schedule);
            });
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'out_of_stock') {
                $query->where('quantity_in_stock', '=', 0);
            } elseif ($request->stock_status === 'low_stock') {
                $query->where('quantity_in_stock', '>', 0)->where('quantity_in_stock', '<=', 10);
            }
        }

        if ($request->filled('expiring_days')) {
            $query->whereDate('expiry_date', '<=', now()->addDays((int) $request->expiring_days))
                ->whereDate('expiry_date', '>=', now());
        }

        $batches = $query->orderBy('expiry_date')
            ->paginate($request->get('per_page', 50));

        return $this->paginated($batches);
    }

    public function adjustments(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $adjustments = \DB::table('inventory_adjustments')
            ->leftJoin('users', 'users.id', '=', 'inventory_adjustments.adjusted_by')
            ->where('inventory_adjustments.company_id', $companyId)
            ->where('inventory_adjustments.outlet_id', $outletId)
            ->select(
                'inventory_adjustments.id',
                'inventory_adjustments.type',
                'inventory_adjustments.reason',
                'inventory_adjustments.created_at',
                'users.name as adjusted_by_name'
            )
            ->orderBy('inventory_adjustments.created_at', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $adjustments,
        ]);
    }

    public function storeAdjustment(Request $request, InventoryService $inventory): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:damage,expiry,count_adjustment,return,other',
            'reason' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.batch_id' => 'required|exists:medicine_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $data = [
            'outlet_id' => $request->user()->outlet_id,
            'adjustment_type' => $request->type,
            'reason' => $request->reason,
            'items' => collect($request->items)->map(function ($item) use ($request) {
                $quantity = (float) $item['quantity'];

                if ($request->type === 'count_adjustment') {
                    $current = (float) MedicineBatch::where('id', $item['batch_id'])->value('quantity_in_stock');
                    $delta = $quantity - $current;
                } else {
                    $delta = -$quantity;
                }

                return [
                    'batch_id' => $item['batch_id'],
                    'quantity_adjusted' => $delta,
                    'reason' => $request->reason,
                ];
            })->all(),
        ];

        try {
            $inventory->createAdjustment($data);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return $this->created(null, 'Stock adjustment recorded successfully.');
    }

    public function reorderSuggestions(Request $request, InventoryService $inventory): JsonResponse
    {
        $grouped = $inventory->reorderSuggestions($request->user()->outlet_id);

        return $this->success($grouped);
    }
}
