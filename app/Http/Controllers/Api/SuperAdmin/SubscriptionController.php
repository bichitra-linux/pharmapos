<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::withoutGlobalScopes()
            ->with('subscriptionPlan')
            ->select('companies.*');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('companies.name', 'like', "%{$search}%")
                    ->orWhere('companies.email', 'like', "%{$search}%");
            });
        }

        if ($request->has('plan_id')) {
            $query->where('subscription_plan_id', $request->input('plan_id'));
        }

        if ($request->has('expiring_soon') && $request->boolean('expiring_soon')) {
            $query->whereNotNull('subscription_expires_at')
                ->whereBetween('subscription_expires_at', [now(), now()->addDays(7)]);
        }

        $subscriptions = $query->latest('companies.created_at')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($subscriptions);
    }

    public function extend(Request $request, Company $company): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:3650',
        ]);

        $days = $request->input('days');

        if ($company->subscription_expires_at && $company->subscription_expires_at->isFuture()) {
            $company->update([
                'subscription_expires_at' => $company->subscription_expires_at->addDays($days),
            ]);
        } else {
            $company->update([
                'subscription_expires_at' => now()->addDays($days),
            ]);
        }

        if ($company->suspended_at) {
            $company->activate();
        }

        return $this->success(
            $company->fresh(),
            "Subscription extended by {$days} days."
        );
    }

    public function cancel(Company $company): JsonResponse
    {
        $company->update([
            'subscription_plan_id' => null,
            'subscription_expires_at' => null,
        ]);

        return $this->success($company->fresh(), 'Subscription cancelled.');
    }
}
