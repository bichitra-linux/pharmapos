<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\CreditLedger;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::where('company_id', $request->user()->company_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $customers = $query->orderBy('name')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = Customer::where('company_id', $request->user()->company_id)
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'blood_group' => $request->blood_group,
            'allergies' => $request->allergies,
            'loyalty_points' => 0,
            'total_dues' => 0,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'data' => $customer,
        ], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function update(StoreCustomerRequest $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $customer->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'data' => $customer->fresh(),
        ]);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
        ]);
    }

    public function history(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $sales = $customer->sales()
            ->where('outlet_id', $request->user()->outlet_id)
            ->with(['items.medicine:id,brand_name', 'payments.paymentMethod'])
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 25));

        $prescriptions = $customer->prescriptions()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer,
                'sales' => $sales,
                'prescriptions' => $prescriptions,
            ],
        ]);
    }

    public function creditLend(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'sale_id' => 'nullable|exists:sales,id',
            'note' => 'nullable|string|max:500',
        ]);

        $amount = (float) $request->amount;

        return DB::transaction(function () use ($request, $customer, $amount) {
            $customer->lockForUpdate()->fresh();
            $balanceAfter = (float) $customer->total_dues + $amount;

            CreditLedger::create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'sale_id' => $request->sale_id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'note' => $request->note,
                'recorded_by' => $request->user()->id,
            ]);

            $customer->increment('total_dues', $amount);

            return $this->success([
                'customer' => $customer->fresh(),
                'balance' => $balanceAfter,
            ], 'Credit recorded.');
        });
    }

    public function creditReceive(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:500',
        ]);

        $amount = (float) $request->amount;

        return DB::transaction(function () use ($request, $customer, $amount) {
            $customer->lockForUpdate()->fresh();
            $newDues = max(0, (float) $customer->total_dues - $amount);
            $balanceAfter = $newDues;

            CreditLedger::create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'type' => 'credit',
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'balance_after' => $balanceAfter,
                'note' => $request->note,
                'recorded_by' => $request->user()->id,
            ]);

            $customer->decrement('total_dues', $amount);

            return $this->success([
                'customer' => $customer->fresh(),
                'balance' => max(0, (float) $customer->fresh()->total_dues),
            ], 'Payment received.');
        });
    }

    public function creditLedger(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $ledger = $customer->creditLedger()
            ->with('recordedBy:id,name')
            ->latest()
            ->paginate($request->get('per_page', 25));

        return $this->paginated($ledger);
    }

    public function creditSummary(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $lastPayment = $customer->creditLedger()
            ->where('type', 'credit')
            ->latest()
            ->first();

        $oldestDebt = $customer->creditLedger()
            ->where('type', 'debit')
            ->oldest()
            ->first();

        return $this->success([
            'current_balance' => (float) $customer->total_dues,
            'credit_limit' => (float) ($customer->credit_limit ?? 0),
            'is_over_limit' => $customer->credit_limit > 0 && (float) $customer->total_dues > (float) $customer->credit_limit,
            'last_payment_at' => $lastPayment?->created_at,
            'oldest_debt_at' => $oldestDebt?->created_at,
        ]);
    }

    public function setCreditLimit(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $request->validate(['credit_limit' => 'required|numeric|min:0']);

        $customer->update(['credit_limit' => (float) $request->credit_limit]);

        return $this->success($customer->fresh(), 'Credit limit updated.');
    }
}
