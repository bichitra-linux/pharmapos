<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SubscriptionController extends Controller
{
    public function plans(): JsonResponse
    {
        $plans = DB::table('subscription_plans')
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
        ]);

        $companyId = $request->user()->company_id;

        $plan = DB::table('subscription_plans')
            ->where('id', $request->plan_id)
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found.'], 404);
        }

        $expiresAt = now()->addDays($plan->duration_days);

        DB::table('companies')
            ->where('id', $companyId)
            ->update([
                'subscription_plan_id' => $plan->id,
                'subscription_expires_at' => $expiresAt,
                'updated_at' => now(),
            ]);

        DB::table('subscription_history')->insert([
            'company_id' => $companyId,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription activated successfully.',
            'data' => [
                'plan' => $plan,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $company = DB::table('companies')
            ->leftJoin('subscription_plans', 'subscription_plans.id', '=', 'companies.subscription_plan_id')
            ->where('companies.id', $companyId)
            ->select(
                'companies.subscription_plan_id',
                'companies.subscription_expires_at',
                'subscription_plans.name as plan_name',
                'subscription_plans.price as plan_price',
                'subscription_plans.duration_days',
                'subscription_plans.features'
            )
            ->first();

        $history = DB::table('subscription_history')
            ->where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $isActive = $company && $company->subscription_expires_at && now()->lt($company->subscription_expires_at);

        return response()->json([
            'success' => true,
            'data' => [
                'current_plan' => $company,
                'is_active' => $isActive,
                'days_remaining' => $company && $company->subscription_expires_at
                    ? max(0, now()->diffInDays($company->subscription_expires_at, false))
                    : 0,
                'history' => $history,
            ],
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        DB::table('companies')
            ->where('id', $companyId)
            ->update([
                'subscription_expires_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('subscription_history')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully.',
        ]);
    }
}
