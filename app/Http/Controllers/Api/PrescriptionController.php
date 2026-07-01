<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrescriptionRequest;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PrescriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Prescription::where('company_id', $request->user()->company_id)
            ->with(['customer:id,name,phone']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prescription_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $prescriptions = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $prescriptions,
        ]);
    }

    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $companyId = $request->user()->company_id;
            $dateStr = now()->format('Ymd');
            $prefix = "RX-{$companyId}-{$dateStr}-";

            $lastPrescription = Prescription::where('company_id', $companyId)
                ->where('prescription_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->max('prescription_number');

            $sequence = 1;
            if ($lastPrescription) {
                $lastSequence = (int) substr($lastPrescription, -4);
                $sequence = $lastSequence >= 9999 ? 1 : $lastSequence + 1;
            }
            $prescriptionNumber = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            $prescription = Prescription::create([
                'company_id' => $companyId,
                'customer_id' => $request->customer_id,
                'prescription_number' => $prescriptionNumber,
                'doctor_name' => $request->doctor_name,
                'prescription_date' => $request->prescription_date ?? now(),
                'notes' => $request->notes,
                'image_path' => $request->hasFile('image') ? $request->file('image')->store('prescriptions', 'public') : null,
                'status' => 'pending',
            ]);

            if ($request->filled('items')) {
                foreach ($request->items as $item) {
                    DB::table('prescription_items')->insert([
                        'prescription_id' => $prescription->id,
                        'medicine_id' => $item['medicine_id'] ?? null,
                        'medicine_name' => $item['medicine_name'] ?? '',
                        'dosage' => $item['dosage'] ?? null,
                        'frequency' => $item['frequency'] ?? null,
                        'duration' => $item['duration'] ?? null,
                        'quantity_prescribed' => $item['quantity'] ?? null,
                        'quantity_dispensed' => 0,
                        'notes' => $item['notes'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $prescription->load(['customer', 'items']);

            return response()->json([
                'success' => true,
                'message' => 'Prescription created successfully.',
                'data' => $prescription,
            ], 201);
        });
    }

    public function show(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $prescription->load(['customer', 'items.medicine']);

        return response()->json([
            'success' => true,
            'data' => $prescription,
        ]);
    }

    public function update(StorePrescriptionRequest $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $prescription->update($request->only([
            'customer_id', 'doctor_name', 'prescription_date', 'notes',
        ]));

        if ($request->hasFile('image')) {
            $prescription->update([
                'image_path' => $request->file('image')->store('prescriptions', 'public'),
            ]);
        }

        $prescription->load(['customer', 'items']);

        return response()->json([
            'success' => true,
            'message' => 'Prescription updated successfully.',
            'data' => $prescription->fresh(),
        ]);
    }

    public function dispense(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.prescription_item_id' => 'required|exists:prescription_items,id',
            'items.*.quantity_dispensed' => 'required|numeric|min:0.01',
        ]);

        foreach ($request->items as $item) {
            DB::table('prescription_items')
                ->where('id', $item['prescription_item_id'])
                ->where('prescription_id', $prescription->id)
                ->update([
                    'quantity_dispensed' => $item['quantity_dispensed'],
                    'updated_at' => now(),
                ]);
        }

        $allDispensed = DB::table('prescription_items')
            ->where('prescription_id', $prescription->id)
            ->where('quantity_dispensed', '<', DB::raw('quantity_prescribed'))
            ->doesntExist();

        if ($allDispensed) {
            $prescription->update(['status' => 'dispensed']);
        } else {
            $prescription->update(['status' => 'partial']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Items dispensed successfully.',
            'data' => $prescription->fresh(),
        ]);
    }
}
