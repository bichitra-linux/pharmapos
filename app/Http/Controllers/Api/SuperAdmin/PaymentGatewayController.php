<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class PaymentGatewayController extends Controller
{
    public function index(): JsonResponse
    {
        $gateways = PaymentGateway::orderBy('code')->get();

        return response()->json([
            'success' => true,
            'data' => $gateways,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:payment_gateways,code',
            'name' => 'required|string|max:100',
            'config' => 'nullable|array',
            'is_sandbox' => 'boolean',
        ]);

        $gateway = PaymentGateway::create([
            'code' => $request->code,
            'name' => $request->name,
            'config' => $request->config ?? [],
            'is_sandbox' => $request->is_sandbox ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $gateway,
        ], 201);
    }

    public function update(Request $request, PaymentGateway $gateway): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'config' => 'nullable|array',
            'is_active' => 'boolean',
            'is_sandbox' => 'boolean',
        ]);

        $gateway->update($request->only(['name', 'config', 'is_active', 'is_sandbox']));

        return response()->json([
            'success' => true,
            'data' => $gateway->fresh(),
        ]);
    }

    public function toggle(PaymentGateway $gateway): JsonResponse
    {
        $gateway->update(['is_active' => ! $gateway->is_active]);

        return response()->json([
            'success' => true,
            'data' => $gateway->fresh(),
        ]);
    }

    public function test(Request $request, PaymentGateway $gateway): JsonResponse
    {
        $config = $gateway->config;

        if (! $gateway->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Gateway is not active.',
            ], 422);
        }

        try {
            $verified = false;
            $message = '';

            switch ($gateway->code) {
                case 'esewa':
                    $response = Http::get($config['verify_url'] ?? 'https://uat.esewa.com.np/epay/transrec', [
                        'amt' => 1,
                        'rid' => 'test',
                        'pid' => 'test',
                        'scd' => $config['merchant_code'] ?? '',
                    ]);
                    $verified = $response->successful();
                    $message = $verified ? 'eSewa gateway is reachable.' : 'eSewa gateway returned an error.';
                    break;

                case 'khalti':
                    $response = Http::withHeaders([
                        'Authorization' => 'Key '.($config['secret_key'] ?? ''),
                    ])->post($config['verify_url'] ?? 'https://a.khalti.com/api/v2/epayment/lookup/', [
                        'pidx' => 'test',
                    ]);
                    $verified = $response->successful() || $response->status() === 400;
                    $message = 'Khalti gateway is reachable.';
                    break;

                default:
                    $verified = true;
                    $message = 'Gateway configuration saved.';
                    break;
            }

            return response()->json([
                'success' => $verified,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
