<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SubscriptionPlan::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $plans = $query->orderBy('sort_order')->orderBy('price_monthly')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'max_outlets' => 'required|integer|min:1',
            'max_users' => 'required|integer|min:1',
            'max_medicines' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $plan = SubscriptionPlan::create($request->only([
            'name',
            'price_monthly',
            'price_yearly',
            'max_outlets',
            'max_users',
            'max_medicines',
            'features',
            'is_active',
            'sort_order',
        ]));

        return $this->created($plan, 'Plan created successfully.');
    }

    public function show(SubscriptionPlan $plan): JsonResponse
    {
        $plan->loadCount('companies');

        return $this->success($plan);
    }

    public function update(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'price_monthly' => 'sometimes|numeric|min:0',
            'price_yearly' => 'sometimes|numeric|min:0',
            'max_outlets' => 'sometimes|integer|min:1',
            'max_users' => 'sometimes|integer|min:1',
            'max_medicines' => 'sometimes|integer|min:0',
            'features' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $plan->update($request->only([
            'name',
            'price_monthly',
            'price_yearly',
            'max_outlets',
            'max_users',
            'max_medicines',
            'features',
            'is_active',
            'sort_order',
        ]));

        return $this->success($plan->fresh(), 'Plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $plan): JsonResponse
    {
        if ($plan->companies()->exists()) {
            return $this->error('Cannot delete plan with active subscribers.', 422);
        }

        $plan->delete();

        return $this->success(null, 'Plan deleted successfully.');
    }

    public function toggle(SubscriptionPlan $plan): JsonResponse
    {
        $plan->update(['is_active' => !$plan->is_active]);

        $status = $plan->is_active ? 'activated' : 'deactivated';

        return $this->success($plan->fresh(), "Plan {$status} successfully.");
    }
}
