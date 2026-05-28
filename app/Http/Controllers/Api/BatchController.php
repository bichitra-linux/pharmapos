<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class BatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('medicine_batches')
            ->join('medicines', 'medicines.id', '=', 'medicine_batches.medicine_id')
            ->where('medicines.company_id', $request->user()->company_id)
            ->select(
                'medicine_batches.*',
                'medicines.brand_name',
                'medicines.generic_name',
            );

        if ($request->filled('medicine_id')) {
            $query->where('medicine_batches.medicine_id', $request->medicine_id);
        }

        if ($request->filled('outlet_id')) {
            $query->where('medicine_batches.outlet_id', $request->outlet_id);
        } else {
            $query->where('medicine_batches.outlet_id', $request->user()->outlet_id);
        }

        if ($request->filled('expiring_within')) {
            $query->whereDate('medicine_batches.expiry_date', '<=', now()->addDays((int) $request->expiring_within))
                ->whereDate('medicine_batches.expiry_date', '>=', now());
        }

        if ($request->boolean('low_stock')) {
            $query->where('medicine_batches.quantity', '>', 0)
                ->where('medicine_batches.quantity', '<=', DB::raw('medicine_batches.reorder_level'));
        }

        $batches = $query->orderBy('medicine_batches.expiry_date')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'medicine_id' => 'required|exists:medicines,id',
            'batch_number' => 'required|string|max:100',
            'expiry_date' => 'required|date|after:today',
            'quantity' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'manufacturing_date' => 'nullable|date',
        ]);

        $medicine = DB::table('medicines')
            ->where('id', $request->medicine_id)
            ->where('company_id', $request->user()->company_id)
            ->first();

        if (! $medicine) {
            return response()->json(['success' => false, 'message' => 'Medicine not found.'], 404);
        }

        $batchId = DB::table('medicine_batches')->insertGetId([
            'medicine_id' => $request->medicine_id,
            'outlet_id' => $request->user()->outlet_id,
            'batch_number' => $request->batch_number,
            'expiry_date' => $request->expiry_date,
            'quantity' => $request->quantity,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price ?? $medicine->selling_price,
            'mrp' => $request->mrp ?? $medicine->mrp,
            'manufacturing_date' => $request->manufacturing_date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $batch = DB::table('medicine_batches')->where('id', $batchId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Batch created successfully.',
            'data' => $batch,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $batch = DB::table('medicine_batches')
            ->join('medicines', 'medicines.id', '=', 'medicine_batches.medicine_id')
            ->where('medicine_batches.id', $id)
            ->where('medicines.company_id', $request->user()->company_id)
            ->select('medicine_batches.*', 'medicines.brand_name', 'medicines.generic_name')
            ->first();

        if (! $batch) {
            return response()->json(['success' => false, 'message' => 'Batch not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $batch,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'batch_number' => 'sometimes|string|max:100',
            'expiry_date' => 'sometimes|date',
            'quantity' => 'sometimes|numeric|min:0',
            'purchase_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
        ]);

        $batch = DB::table('medicine_batches')
            ->join('medicines', 'medicines.id', '=', 'medicine_batches.medicine_id')
            ->where('medicine_batches.id', $id)
            ->where('medicines.company_id', $request->user()->company_id)
            ->select('medicine_batches.id')
            ->first();

        if (! $batch) {
            return response()->json(['success' => false, 'message' => 'Batch not found.'], 404);
        }

        DB::table('medicine_batches')
            ->where('id', $id)
            ->update(array_merge(
                $request->only(['batch_number', 'expiry_date', 'quantity', 'purchase_price', 'selling_price', 'mrp']),
                ['updated_at' => now()]
            ));

        $updated = DB::table('medicine_batches')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Batch updated successfully.',
            'data' => $updated,
        ]);
    }

    public function expiringSoon(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 90);
        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $batches = DB::table('medicine_batches')
            ->join('medicines', 'medicines.id', '=', 'medicine_batches.medicine_id')
            ->where('medicines.company_id', $companyId)
            ->where('medicine_batches.outlet_id', $outletId)
            ->where('medicine_batches.quantity', '>', 0)
            ->whereDate('medicine_batches.expiry_date', '<=', now()->addDays($days))
            ->whereDate('medicine_batches.expiry_date', '>=', now())
            ->select(
                'medicine_batches.*',
                'medicines.brand_name',
                'medicines.generic_name',
            )
            ->orderBy('medicine_batches.expiry_date')
            ->get()
            ->map(fn ($batch) => (array) $batch);

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }
}
