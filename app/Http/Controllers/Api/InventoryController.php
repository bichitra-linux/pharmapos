<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicineBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function storeAdjustment(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:damage,expiry,count_adjustment,return,other',
            'reason' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.batch_id' => 'required|exists:medicine_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        return \DB::transaction(function () use ($request, $companyId, $outletId) {
            $adjustment = \DB::table('inventory_adjustments')->insertGetId([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'type' => $request->type,
                'reason' => $request->reason,
                'adjusted_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Batch-fetch all batches with lock to avoid race conditions
            $batchIds = collect($request->items)->pluck('batch_id')->unique();
            $batches = \DB::table('medicine_batches')
                ->where('company_id', $companyId)
                ->where('outlet_id', $outletId)
                ->whereIn('id', $batchIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($request->items as $item) {
                $batch = $batches->get($item['batch_id']);

                if (!$batch) {
                    continue;
                }

                $quantityChange = $request->type === 'count_adjustment'
                    ? (float) $item['quantity']
                    : -(float) $item['quantity'];

                \DB::table('adjustment_items')->insert([
                    'adjustment_id' => $adjustment,
                    'medicine_id' => $item['medicine_id'],
                    'batch_id' => $item['batch_id'],
                    'quantity' => $quantityChange,
                    'reason' => $request->reason,
                    'created_at' => now(),
                ]);

                if ($quantityChange >= 0) {
                    \DB::table('medicine_batches')
                        ->where('id', $item['batch_id'])
                        ->increment('quantity_in_stock', $quantityChange);
                } else {
                    \DB::table('medicine_batches')
                        ->where('id', $item['batch_id'])
                        ->decrement('quantity_in_stock', abs($quantityChange));
                }
            }

            return $this->created(null, 'Stock adjustment recorded successfully.');
        });
    }
}
