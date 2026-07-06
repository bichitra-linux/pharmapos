<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'password' => $request->admin_password,
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

    public function impersonate(Request $request, Company $tenant): JsonResponse
    {
        $user = $tenant->users()->where('is_active', true)->first();

        if (!$user) {
            return $this->error('No active user found in this tenant.', 404);
        }

        $token = $user->createToken('impersonation-' . $request->user()->id, ['*'], now()->addHours(1))->plainTextToken;

        AuditLog::create([
            'company_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'super_admin_impersonated',
            'model_type' => 'Company',
            'model_id' => $tenant->id,
            'new_values' => [
                'super_admin_id' => $request->user()->id,
                'super_admin_name' => $request->user()->name,
                'target_user' => $user->email,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return $this->success([
            'token' => $token,
            'user' => $user->load('outlet'),
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'expires_at' => now()->addHours(1),
        ], 'Impersonation token generated.');
    }

    public function usage(Company $tenant): JsonResponse
    {
        $usage = [
            'users_count' => $tenant->users()->withoutGlobalScopes()->count(),
            'outlets_count' => $tenant->outlets()->withoutGlobalScopes()->count(),
            'medicines_count' => $tenant->medicines()->withoutGlobalScopes()->count(),
            'customers_count' => $tenant->customers()->withoutGlobalScopes()->count(),
            'sales_this_month' => $tenant->sales()->withoutGlobalScopes()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'sales_total' => (float) $tenant->sales()->withoutGlobalScopes()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount'),
        ];

        $plan = $tenant->subscriptionPlan;

        if ($plan) {
            $usage['plan_limit_medicines'] = $plan->max_medicines;
            $usage['plan_limit_users'] = $plan->max_users;
            $usage['plan_limit_outlets'] = $plan->max_outlets;
            $usage['medicines_percent'] = $plan->max_medicines > 0 ? round(($usage['medicines_count'] / $plan->max_medicines) * 100) : 0;
            $usage['users_percent'] = $plan->max_users > 0 ? round(($usage['users_count'] / $plan->max_users) * 100) : 0;
            $usage['outlets_percent'] = $plan->max_outlets > 0 ? round(($usage['outlets_count'] / $plan->max_outlets) * 100) : 0;
        }

        return $this->success($usage);
    }
}
