<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicineRequest;
use App\Models\Medicine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class MedicineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $outletId = $request->user()->outlet_id;

        $query = Medicine::where('company_id', $request->user()->company_id)
            ->with(['medicineCategory', 'manufacturer', 'saltComposition'])
            ->with(['batches' => function ($b) use ($outletId) {
                $b->where('outlet_id', $outletId)
                    ->where('quantity_in_stock', '>', 0)
                    ->whereDate('expiry_date', '>', now())
                    ->orderBy('expiry_date')
                    ->select('id', 'medicine_id', 'outlet_id', 'batch_number', 'expiry_date', 'quantity_in_stock', 'quantity_in_pieces', 'mrp_per_unit', 'selling_price_per_unit');
            }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('brand_name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('hsn_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('medicine_category_id', $request->category_id);
        }

        if ($request->filled('manufacturer_id')) {
            $query->where('manufacturer_id', $request->manufacturer_id);
        }

        if ($request->filled('schedule')) {
            $query->where('schedule_type', $request->schedule);
        }

        if ($request->filled('stock_status')) {
            $outletId = $request->user()->outlet_id;
            if ($request->stock_status === 'in_stock') {
                $query->whereHas('batches', fn ($q) => $q->where('outlet_id', $outletId)->where('quantity_in_stock', '>', 0));
            } elseif ($request->stock_status === 'out_of_stock') {
                $query->whereDoesntHave('batches', fn ($q) => $q->where('outlet_id', $outletId)->where('quantity_in_stock', '>', 0));
            }
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $allowedSorts = ['brand_name', 'generic_name', 'created_at', 'schedule_type', 'dosage_form'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts) ? $request->get('sort_by') : 'brand_name';

        $medicines = $query->orderBy($sortBy, $request->get('sort_dir', 'asc'))
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $medicines,
        ]);
    }

    public function store(StoreMedicineRequest $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        // Soft duplicate check: warn if same brand+strength+manufacturer exists
        $duplicate = Medicine::where('company_id', $companyId)
            ->where('brand_name', $request->brand_name)
            ->where('manufacturer_id', $request->manufacturer_id)
            ->where('id', '!=', $request->route('medicine') ?? 0)
            ->when($request->strength, fn ($q, $v) => $q->where('strength', $v))
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Similar medicine already exists.',
                'warning' => 'duplicate',
                'similar_id' => $duplicate->id,
                'similar' => [
                    'brand_name' => $duplicate->brand_name,
                    'strength' => $duplicate->strength,
                    'manufacturer' => $duplicate->manufacturer?->name,
                ],
            ], 409);
        }

        $medicine = Medicine::create([
            'company_id' => $companyId,
            'brand_name' => $request->brand_name,
            'generic_name' => $request->generic_name,
            'medicine_category_id' => $request->medicine_category_id,
            'manufacturer_id' => $request->manufacturer_id,
            'salt_composition_id' => $request->salt_composition_id,
            'hsn_code' => $request->hsn_code,
            'barcode' => $request->barcode,
            'schedule_type' => $request->schedule_type ?? 'otc',
            'dosage_form' => $request->dosage_form,
            'strength' => $request->strength,
            'unit_type' => $request->unit_type ?? 'strip',
            'units_per_pack' => $request->units_per_pack ?? 1,
            'is_prescription_required' => $request->boolean('is_prescription_required', false),
            'is_active' => $request->boolean('is_active', true),
            'description' => $request->description,
            'storage_conditions' => $request->storage_conditions,
            'is_temperature_sensitive' => $request->boolean('is_temperature_sensitive', false),
            'image' => $request->image,
        ]);

        $medicine->load(['medicineCategory', 'manufacturer', 'saltComposition']);

        return response()->json([
            'success' => true,
            'message' => 'Medicine created successfully.',
            'data' => $medicine,
        ], 201);
    }

    public function show(Request $request, Medicine $medicine): JsonResponse
    {
        if ($medicine->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $medicine->load([
            'medicineCategory',
            'manufacturer',
            'saltComposition',
            'batches' => fn ($q) => $q->where('outlet_id', $request->user()->outlet_id)->where('quantity_in_stock', '>', 0)->orderBy('expiry_date'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $medicine,
        ]);
    }

    public function update(StoreMedicineRequest $request, Medicine $medicine): JsonResponse
    {
        if ($medicine->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $medicine->update($request->validated());

        $medicine->load(['medicineCategory', 'manufacturer', 'saltComposition']);

        return response()->json([
            'success' => true,
            'message' => 'Medicine updated successfully.',
            'data' => $medicine,
        ]);
    }

    public function destroy(Request $request, Medicine $medicine): JsonResponse
    {
        if ($medicine->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $hasSales = DB::table('sale_items')->where('medicine_id', $medicine->id)->exists();
        $hasPurchases = DB::table('purchase_items')->where('medicine_id', $medicine->id)->exists();

        if ($hasSales || $hasPurchases) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete medicine with existing sales or purchases. Deactivate it instead.',
            ], 422);
        }

        $medicine->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medicine deleted successfully.',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1']);

        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;
        $query = $request->q;

        $medicines = Medicine::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('brand_name', 'like', "%{$query}%")
                    ->orWhere('generic_name', 'like', "%{$query}%")
                    ->orWhere('barcode', $query);
            })
            ->with(['batches' => function ($b) use ($outletId) {
                $b->where('outlet_id', $outletId)
                    ->where('quantity_in_stock', '>', 0)
                    ->whereDate('expiry_date', '>', now())
                    ->orderBy('expiry_date');
            }])
            ->limit(20)
            ->get()
            ->map(fn ($medicine) => [
                'id' => $medicine->id,
                'brand_name' => $medicine->brand_name,
                'generic_name' => $medicine->generic_name,
                'schedule_type' => $medicine->schedule_type,
                'dosage_form' => $medicine->dosage_form,
                'strength' => $medicine->strength,
                'units_per_pack' => $medicine->units_per_pack,
                'batches' => $medicine->batches->map(fn ($batch) => [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date,
                    'quantity_in_stock' => (float) $batch->quantity_in_stock,
                    'selling_price_per_unit' => $batch->selling_price_per_unit,
                    'mrp_per_unit' => $batch->mrp_per_unit,
                ]),
                'total_stock' => $medicine->batches->sum('quantity_in_stock'),
            ]);

        return response()->json([
            'success' => true,
            'data' => $medicines,
        ]);
    }

    public function batches(Request $request, Medicine $medicine): JsonResponse
    {
        if ($medicine->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $batches = $medicine->batches()
            ->where('outlet_id', $request->user()->outlet_id)
            ->where('quantity_in_stock', '>', 0)
            ->orderBy('expiry_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    public function substitutes(Request $request, Medicine $medicine): JsonResponse
    {
        if ($medicine->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $medicine->substitutes,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            try {
                $data = array_combine($header, $row);

                Medicine::updateOrCreate(
                    [
                        'company_id' => $request->user()->company_id,
                        'brand_name' => $data['brand_name'] ?? '',
                    ],
                    [
                        'generic_name' => $data['generic_name'] ?? null,
                        'hsn_code' => $data['hsn_code'] ?? null,
                        'barcode' => $data['barcode'] ?? null,
                        'schedule_type' => $data['schedule_type'] ?? 'otc',
                        'dosage_form' => $data['dosage_form'] ?? null,
                        'strength' => $data['strength'] ?? null,
                        'unit_type' => $data['unit_type'] ?? 'strip',
                        'units_per_pack' => (int) ($data['units_per_pack'] ?? 1),
                        'is_prescription_required' => ($data['schedule_type'] ?? 'otc') !== 'otc',
                        'is_active' => true,
                    ]
                );
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$rowNumber}: ".$e->getMessage();
            }
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'message' => "{$imported} medicines imported successfully.",
            'data' => [
                'imported' => $imported,
                'errors' => $errors,
            ],
        ]);
    }
}
