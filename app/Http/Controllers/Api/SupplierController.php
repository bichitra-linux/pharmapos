<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::where('company_id', $request->user()->company_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $suppliers,
        ]);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'pan_number' => $request->pan_number,
            'bank_details' => $request->bank_details,
            'credit_days' => $request->credit_days ?? 30,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data' => $supplier,
        ], 201);
    }

    public function show(Request $request, Supplier $supplier): JsonResponse
    {
        if ($supplier->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        if ($supplier->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $supplier->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully.',
            'data' => $supplier->fresh(),
        ]);
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        if ($supplier->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully.',
        ]);
    }

    public function ledger(Request $request, Supplier $supplier): JsonResponse
    {
        if ($supplier->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $purchases = DB::table('purchases')
            ->where('supplier_id', $supplier->id)
            ->where('company_id', $request->user()->company_id)
            ->select('id', 'purchase_number', 'total', 'paid_amount', 'status', 'created_at')
            ->get();

        $payments = DB::table('supplier_payments')
            ->where('supplier_id', $supplier->id)
            ->where('company_id', $request->user()->company_id)
            ->select('id', 'amount', 'payment_method', 'reference_number', 'notes', 'created_at')
            ->orderByDesc('created_at')
            ->get();

        $totalPurchases = $purchases->sum('total');
        $totalPaid = $purchases->sum('paid_amount') + $payments->sum('amount');
        $balance = $totalPurchases - $totalPaid;

        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => $supplier,
                'purchases' => $purchases,
                'payments' => $payments,
                'summary' => [
                    'total_purchases' => (float) $totalPurchases,
                    'total_paid' => (float) $totalPaid,
                    'balance' => (float) $balance,
                ],
            ],
        ]);
    }
}
