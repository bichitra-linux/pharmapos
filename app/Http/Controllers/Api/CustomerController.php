<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
