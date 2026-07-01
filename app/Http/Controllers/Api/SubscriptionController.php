<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SubscriptionController extends Controller
{
    public function plans(): JsonResponse
    {
        $plans = DB::table('subscription_plans')
            ->where('is_active', true)
            ->orderBy('price_monthly')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    public function gateways(): JsonResponse
    {
        $gateways = PaymentGateway::where('is_active', true)
            ->get(['id', 'code', 'name', 'is_sandbox']);

        return response()->json([
            'success' => true,
            'data' => $gateways,
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'nullable|string|max:50',
            'gateway_code' => 'nullable|string|exists:payment_gateways,code',
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

        $planHasYearly = $plan->price_yearly > 0 && $plan->price_yearly > $plan->price_monthly;
        $expiresAt = $planHasYearly ? now()->addDays(365) : now()->addDays(30);
        $amount = $planHasYearly ? $plan->price_yearly : $plan->price_monthly;

        // If gateway selected, initiate payment (not marking active yet)
        if ($request->gateway_code) {
            $gateway = PaymentGateway::where('code', $request->gateway_code)
                ->where('is_active', true)
                ->first();

            if (! $gateway) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected payment gateway is not active.',
                ], 422);
            }

            $paymentId = DB::table('subscription_payments')->insertGetId([
                'company_id' => $companyId,
                'plan_id' => $plan->id,
                'amount' => $amount,
                'payment_method' => 'online',
                'gateway' => $gateway->code,
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Redirecting to payment gateway.',
                'data' => [
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                    'gateway' => $gateway->code,
                    'gateway_config' => $gateway->config,
                    'is_sandbox' => $gateway->is_sandbox,
                ],
            ]);
        }

        // Direct activation (cash/admin)
        DB::table('companies')
            ->where('id', $companyId)
            ->update([
                'subscription_plan_id' => $plan->id,
                'subscription_expires_at' => $expiresAt,
                'updated_at' => now(),
            ]);

        DB::table('subscription_payments')->insert([
            'company_id' => $companyId,
            'plan_id' => $plan->id,
            'amount' => $amount,
            'payment_method' => $request->payment_method ?? 'cash',
            'gateway' => $request->reference_number,
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
                'subscription_plans.price_monthly as plan_price',
                'subscription_plans.features'
            )
            ->first();

        $history = DB::table('subscription_payments')
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

        DB::table('subscription_payments')
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
