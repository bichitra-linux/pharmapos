<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class InventoryController extends Controller
{
    public function stock(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $query = DB::table('medicines')
            ->leftJoin('medicine_batches', function ($join) use ($outletId) {
                $join->on('medicine_batches.medicine_id', '=', 'medicines.id')
                    ->where('medicine_batches.outlet_id', '=', $outletId);
            })
            ->leftJoin('medicine_categories', 'medicine_categories.id', '=', 'medicines.medicine_category_id')
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'medicines.manufacturer_id')
            ->where('medicines.company_id', $companyId)
            ->where('medicines.is_active', true)
            ->select(
                'medicines.id',
                'medicines.brand_name',
                'medicines.generic_name',
                'medicines.hsn_code',
                'medicines.schedule_type',
                'medicines.dosage_form',
                'medicines.strength',
                'medicines.unit_type',
                'medicine_categories.name as category_name',
                'manufacturers.name as manufacturer_name',
                DB::raw('COALESCE(SUM(medicine_batches.quantity_in_stock), 0) as current_stock'),
                DB::raw('COALESCE(SUM(medicine_batches.quantity_in_stock * medicine_batches.purchase_price_per_unit), 0) as stock_value'),
            )
            ->groupBy(
                'medicines.id', 'medicines.brand_name', 'medicines.generic_name',
                'medicines.hsn_code', 'medicines.schedule_type', 'medicines.dosage_form',
                'medicines.strength', 'medicines.unit_type',
                'medicine_categories.name', 'manufacturers.name'
            );

        if ($request->filled('category_id')) {
            $query->where('medicines.medicine_category_id', $request->category_id);
        }

        if ($request->filled('manufacturer_id')) {
            $query->where('medicines.manufacturer_id', $request->manufacturer_id);
        }

        if ($request->filled('schedule')) {
            $query->where('medicines.schedule_type', $request->schedule);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'in_stock') {
                $query->having('current_stock', '>', 0);
            } elseif ($request->stock_status === 'out_of_stock') {
                $query->having('current_stock', '=', 0);
            } elseif ($request->stock_status === 'low_stock') {
                $query->havingRaw('current_stock > 0');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('medicines.brand_name', 'like', "%{$search}%")
                    ->orWhere('medicines.generic_name', 'like', "%{$search}%");
            });
        }

        $stock = $query->orderBy('medicines.brand_name')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $stock,
        ]);
    }

    public function adjustments(Request $request): JsonResponse
    {
        $query = DB::table('inventory_adjustments')
            ->join('users', 'users.id', '=', 'inventory_adjustments.adjusted_by')
            ->where('inventory_adjustments.company_id', $request->user()->company_id)
            ->where('inventory_adjustments.outlet_id', $request->user()->outlet_id)
            ->select(
                'inventory_adjustments.*',
                'users.name as adjusted_by_name'
            );

        if ($request->filled('type')) {
            $query->where('inventory_adjustments.type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('inventory_adjustments.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('inventory_adjustments.created_at', '<=', $request->date_to);
        }

        $adjustments = $query->orderByDesc('inventory_adjustments.created_at')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $adjustments,
        ]);
    }

    public function storeAdjustment(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|exists:medicine_batches,id',
            'type' => 'required|in:damage,expiry,count_adjustment,return,other',
            'quantity' => 'required|numeric',
            'reason' => 'required|string|max:500',
        ]);

        return DB::transaction(function () use ($request) {
            $companyId = $request->user()->company_id;
            $outletId = $request->user()->outlet_id;

            $batch = DB::table('medicine_batches')
                ->where('id', $request->batch_id)
                ->where('outlet_id', $outletId)
                ->first();

            if (! $batch) {
                return response()->json(['success' => false, 'message' => 'Batch not found.'], 404);
            }

            $previousQuantity = (float) $batch->quantity_in_stock;
            $adjustmentQuantity = (float) $request->quantity;

            if ($request->type === 'count_adjustment') {
                $newQuantity = $adjustmentQuantity;
            } else {
                $newQuantity = $previousQuantity - abs($adjustmentQuantity);
            }

            if ($newQuantity < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adjustment would result in negative stock.',
                ], 422);
            }

            DB::table('inventory_adjustments')->insert([
                'company_id' => $companyId,
                'outlet_id' => $outletId,
                'type' => $request->type,
                'reason' => $request->reason,
                'adjusted_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('medicine_batches')
                ->where('id', $request->batch_id)
                ->update([
                    'quantity_in_stock' => $newQuantity,
                    'updated_at' => now(),
                ]);

            if ($newQuantity <= 0) {
                DB::table('medicine_batches')
                    ->where('id', $request->batch_id)
                    ->update(['is_active' => false]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment recorded successfully.',
                'data' => [
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                ],
            ], 201);
        });
    }
}
