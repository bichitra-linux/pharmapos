<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SupplierPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('supplier_payments')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_payments.supplier_id')
            ->where('supplier_payments.company_id', $request->user()->company_id)
            ->select(
                'supplier_payments.*',
                'suppliers.name as supplier_name',
                'suppliers.company_name'
            );

        if ($request->filled('supplier_id')) {
            $query->where('supplier_payments.supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('supplier_payments.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('supplier_payments.created_at', '<=', $request->date_to);
        }

        $payments = $query->orderByDesc('supplier_payments.created_at')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $companyId = $request->user()->company_id;

        $supplier = DB::table('suppliers')
            ->where('id', $request->supplier_id)
            ->where('company_id', $companyId)
            ->first();

        if (! $supplier) {
            return response()->json(['success' => false, 'message' => 'Supplier not found.'], 404);
        }

        $paymentId = DB::table('supplier_payments')->insertGetId([
            'company_id' => $companyId,
            'supplier_id' => $request->supplier_id,
            'purchase_id' => $request->purchase_id,
            'user_id' => $request->user()->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'payment_date' => $request->payment_date ?? now(),
            'notes' => $request->notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update purchase paid amount if linked
        if ($request->purchase_id) {
            DB::table('purchases')
                ->where('id', $request->purchase_id)
                ->where('company_id', $companyId)
                ->increment('paid_amount', $request->amount);

            $purchase = DB::table('purchases')->where('id', $request->purchase_id)->first();
            if ($purchase && $purchase->paid_amount >= $purchase->total) {
                DB::table('purchases')
                    ->where('id', $request->purchase_id)
                    ->update(['status' => 'paid']);
            }
        }

        $payment = DB::table('supplier_payments')->where('id', $paymentId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data' => $payment,
        ], 201);
    }
}
