<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class NarcoticsRegisterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('narcotics_register')
            ->join('medicines', 'medicines.id', '=', 'narcotics_register.medicine_id')
            ->leftJoin('customers', 'customers.id', '=', 'narcotics_register.customer_id')
            ->leftJoin('sales', 'sales.id', '=', 'narcotics_register.sale_id')
            ->where('narcotics_register.company_id', $request->user()->company_id)
            ->where('narcotics_register.outlet_id', $request->user()->outlet_id)
            ->select(
                'narcotics_register.*',
                'medicines.brand_name',
                'medicines.generic_name',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
                'sales.invoice_number'
            );

        if ($request->filled('date_from')) {
            $query->whereDate('narcotics_register.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('narcotics_register.created_at', '<=', $request->date_to);
        }

        if ($request->filled('medicine_id')) {
            $query->where('narcotics_register.medicine_id', $request->medicine_id);
        }

        $entries = $query->orderByDesc('narcotics_register.created_at')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $entries,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'medicine_id' => 'required|exists:medicines,id',
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'prescription_number' => 'nullable|string|max:100',
            'doctor_name' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'batch_number' => 'nullable|string|max:100',
            'patient_name' => 'nullable|string|max:255',
            'patient_address' => 'nullable|string|max:500',
            'purpose' => 'nullable|string|max:500',
        ]);

        $companyId = $request->user()->company_id;
        $outletId = $request->user()->outlet_id;

        $entryId = DB::table('narcotics_register')->insertGetId([
            'company_id' => $companyId,
            'outlet_id' => $outletId,
            'medicine_id' => $request->medicine_id,
            'sale_id' => $request->sale_id,
            'customer_id' => $request->customer_id,
            'user_id' => $request->user()->id,
            'prescription_number' => $request->prescription_number,
            'doctor_name' => $request->doctor_name,
            'quantity' => $request->quantity,
            'batch_number' => $request->batch_number,
            'patient_name' => $request->patient_name,
            'patient_address' => $request->patient_address,
            'purpose' => $request->purpose,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $entry = DB::table('narcotics_register')->where('id', $entryId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Narcotics register entry created.',
            'data' => $entry,
        ], 201);
    }
}
