<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::withoutGlobalScopes()
            ->withCount('users')
            ->with('subscriptionPlan');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->whereNull('suspended_at')->where('is_active', true);
            } elseif ($status === 'suspended') {
                $query->whereNotNull('suspended_at');
            }
        }

        $tenants = $query->latest()->paginate($request->input('per_page', 15));

        return $this->paginated($tenants);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'pan_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'drug_license_number' => 'nullable|string|max:100',
            'pharmacy_license_number' => 'nullable|string|max:100',
            'pharmacist_name' => 'nullable|string|max:255',
            'pharmacist_registration_number' => 'nullable|string|max:100',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        $company = DB::transaction(function () use ($request) {
            $company = Company::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'pan_number' => $request->pan_number,
                'vat_number' => $request->vat_number,
                'drug_license_number' => $request->drug_license_number,
                'pharmacy_license_number' => $request->pharmacy_license_number,
                'pharmacist_name' => $request->pharmacist_name,
                'pharmacist_registration_number' => $request->pharmacist_registration_number,
                'subscription_plan_id' => $request->subscription_plan_id,
                'subscription_expires_at' => $request->subscription_plan_id
                    ? now()->addDays(30)
                    : null,
                'is_active' => true,
            ]);

            $outlet = Outlet::create([
                'company_id' => $company->id,
                'name' => 'Main Outlet',
                'address' => $request->address,
                'phone' => $request->phone,
                'drug_license_number' => $request->drug_license_number,
                'is_main_outlet' => true,
                'is_active' => true,
            ]);

            User::create([
                'company_id' => $company->id,
                'outlet_id' => $outlet->id,
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'role' => 'owner',
                'is_active' => true,
            ]);

            $defaultPaymentMethods = [
                ['name' => 'Cash', 'code' => 'cash', 'type' => 'cash', 'sort_order' => 1],
                ['name' => 'eSewa', 'code' => 'esewa', 'type' => 'digital_wallet', 'sort_order' => 2],
                ['name' => 'Khalti', 'code' => 'khalti', 'type' => 'digital_wallet', 'sort_order' => 3],
                ['name' => 'IME Pay', 'code' => 'ime_pay', 'type' => 'digital_wallet', 'sort_order' => 4],
                ['name' => 'Fonepay', 'code' => 'fonepay', 'type' => 'digital_wallet', 'sort_order' => 5],
                ['name' => 'ConnectIPS', 'code' => 'connectips', 'type' => 'bank_transfer', 'sort_order' => 6],
                ['name' => 'Card', 'code' => 'card', 'type' => 'card', 'sort_order' => 7],
                ['name' => 'Bank Transfer', 'code' => 'bank_transfer', 'type' => 'bank_transfer', 'sort_order' => 8],
            ];

            foreach ($defaultPaymentMethods as $method) {
                PaymentMethod::create(array_merge($method, [
                    'company_id' => $company->id,
                    'is_active' => true,
                ]));
            }

            return $company;
        });

        return $this->created($company->load('subscriptionPlan'), 'Tenant created successfully.');
    }

    public function show(Company $tenant): JsonResponse
    {
        $tenant->load([
            'subscriptionPlan',
            'users' => fn ($q) => $q->withoutGlobalScopes()->latest()->take(20),
        ]);

        $tenant->loadCount(['users', 'outlets', 'medicines']);

        return $this->success($tenant);
    }

    public function update(Request $request, Company $tenant): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:companies,email,' . $tenant->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'pan_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'drug_license_number' => 'nullable|string|max:100',
            'pharmacy_license_number' => 'nullable|string|max:100',
            'pharmacist_name' => 'nullable|string|max:255',
            'pharmacist_registration_number' => 'nullable|string|max:100',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $tenant->update($request->only([
            'name',
            'email',
            'phone',
            'address',
            'pan_number',
            'vat_number',
            'drug_license_number',
            'pharmacy_license_number',
            'pharmacist_name',
            'pharmacist_registration_number',
            'subscription_plan_id',
            'is_active',
        ]));

        if ($request->has('name')) {
            $tenant->update(['slug' => Str::slug($request->name)]);
        }

        return $this->success($tenant->fresh(), 'Tenant updated successfully.');
    }

    public function suspend(Request $request, Company $tenant): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $tenant->suspend($request->input('reason', ''));

        return $this->success($tenant->fresh(), 'Tenant suspended successfully.');
    }

    public function activate(Company $tenant): JsonResponse
    {
        $tenant->activate();

        return $this->success($tenant->fresh(), 'Tenant activated successfully.');
    }

    public function destroy(Company $tenant): JsonResponse
    {
        $tenant->delete();

        return $this->success(null, 'Tenant deleted successfully.');
    }
}
