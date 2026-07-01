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
                'suppliers.name as supplier_name'
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

        // Validate purchase belongs to the same supplier
        if ($request->purchase_id) {
            $purchase = DB::table('purchases')
                ->where('id', $request->purchase_id)
                ->where('company_id', $companyId)
                ->first();

            if (! $purchase) {
                return response()->json(['success' => false, 'message' => 'Purchase not found.'], 404);
            }

            if ($purchase->supplier_id != $request->supplier_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase does not belong to the specified supplier.',
                ], 422);
            }
        }

        $paymentId = DB::transaction(function () use ($request, $companyId) {
            $paymentId = DB::table('supplier_payments')->insertGetId([
                'company_id' => $companyId,
                'supplier_id' => $request->supplier_id,
                'purchase_id' => $request->purchase_id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($request->purchase_id) {
                // Lock the purchase row to prevent race conditions on paid_amount
                $purchase = DB::table('purchases')
                    ->where('id', $request->purchase_id)
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->first();

                if ($purchase) {
                    $newPaidAmount = $purchase->paid_amount + $request->amount;
                    $paymentStatus = 'due';
                    if ($newPaidAmount >= $purchase->total) {
                        $paymentStatus = 'paid';
                    } elseif ($newPaidAmount > 0) {
                        $paymentStatus = 'partial';
                    }

                    DB::table('purchases')
                        ->where('id', $request->purchase_id)
                        ->update([
                            'paid_amount' => $newPaidAmount,
                            'payment_status' => $paymentStatus,
                        ]);
                }
            }

            return $paymentId;
        });

        $payment = DB::table('supplier_payments')->where('id', $paymentId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data' => $payment,
        ], 201);
    }
}
