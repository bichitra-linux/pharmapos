<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PaymentController extends Controller
{
    public function initiateEsewa(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $sale = Sale::where('id', $request->sale_id)
            ->where('company_id', $request->user()->company_id)
            ->firstOrFail();

        $esewaConfig = config('services.esewa');

        $params = [
            'amt' => $sale->total - ($sale->vat_amount ?? 0),
            'psc' => 0,
            'pdc' => 0,
            'txAmt' => $sale->vat_amount ?? 0,
            'tAmt' => $sale->total,
            'pid' => $sale->invoice_number,
            'scd' => $esewaConfig['merchant_code'] ?? '',
            'su' => $esewaConfig['success_url'] ?? url('/api/payments/callback/esewa'),
            'fu' => $esewaConfig['failure_url'] ?? url('/api/payments/callback/esewa'),
        ];

        $gatewayUrl = ($esewaConfig['sandbox'] ?? true)
            ? 'https://uat.esewa.com.np/epay/main'
            : 'https://esewa.com.np/epay/main';

        return response()->json([
            'success' => true,
            'data' => [
                'gateway_url' => $gatewayUrl,
                'params' => $params,
                'sale_id' => $sale->id,
            ],
        ]);
    }

    public function initiateKhalti(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $sale = Sale::where('id', $request->sale_id)
            ->where('company_id', $request->user()->company_id)
            ->firstOrFail();

        $khaltiConfig = config('services.khalti');

        $response = Http::withHeaders([
            'Authorization' => 'Key '.($khaltiConfig['secret_key'] ?? ''),
        ])->post($khaltiConfig['api_url'] ?? 'https://a.khalti.com/api/v2/epayment/initiate/', [
            'return_url' => $khaltiConfig['return_url'] ?? url('/api/payments/callback/khalti'),
            'website_url' => config('app.url'),
            'amount' => (int) ($sale->total * 100), // Khalti uses paisa
            'purchase_order_id' => $sale->invoice_number,
            'purchase_order_name' => 'Invoice '.$sale->invoice_number,
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'data' => $response->json(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to initiate Khalti payment.',
            'data' => $response->json(),
        ], 400);
    }

    public function initiateFonepay(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $sale = Sale::where('id', $request->sale_id)
            ->where('company_id', $request->user()->company_id)
            ->firstOrFail();

        $fonepayConfig = config('services.fonepay');

        $params = [
            'PID' => $fonepayConfig['merchant_id'] ?? '',
            'MD' => 'P',
            'AMT' => $sale->total,
            'CRN' => 'NPR',
            'DT' => now()->format('Y-m-d'),
            'R1' => $sale->invoice_number,
            'R2' => 'Payment for invoice',
            'RU' => $fonepayConfig['return_url'] ?? url('/api/payments/callback/fonepay'),
        ];

        $params['DV'] = hash_hmac('sha256', implode(',', $params), $fonepayConfig['secret_key'] ?? '');

        $gatewayUrl = ($fonepayConfig['sandbox'] ?? true)
            ? 'https://dev-client.fonepay.com/api/merchantRequest'
            : 'https://client.fonepay.com/api/merchantRequest';

        return response()->json([
            'success' => true,
            'data' => [
                'gateway_url' => $gatewayUrl,
                'params' => $params,
                'sale_id' => $sale->id,
            ],
        ]);
    }

    public function initiateConnectIPS(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $sale = Sale::where('id', $request->sale_id)
            ->where('company_id', $request->user()->company_id)
            ->firstOrFail();

        $connectIpsConfig = config('services.connect_ips');

        $params = [
            'MID' => $connectIpsConfig['merchant_id'] ?? '',
            'AMT' => $sale->total,
            'PID' => $sale->invoice_number,
            'DT' => now()->format('Y-m-d'),
            'CRN' => 'NPR',
            'R1' => 'Payment for invoice '.$sale->invoice_number,
            'R2' => '',
            'RU' => $connectIpsConfig['return_url'] ?? url('/api/payments/callback/connectips'),
        ];

        $params['DV'] = hash_hmac('sha256', implode(',', $params), $connectIpsConfig['secret_key'] ?? '');

        $gatewayUrl = ($connectIpsConfig['sandbox'] ?? true)
            ? 'https://uat.connectips.com.np/payment/page'
            : 'https://connectips.com.np/payment/page';

        return response()->json([
            'success' => true,
            'data' => [
                'gateway_url' => $gatewayUrl,
                'params' => $params,
                'sale_id' => $sale->id,
            ],
        ]);
    }

    public function callback(Request $request, string $gateway): JsonResponse
    {
        Log::info('Payment callback received', [
            'gateway' => $gateway,
            'request_data' => $request->except(['secret', 'key', 'token']),
        ]);

        $validGateways = ['esewa', 'khalti', 'fonepay', 'connectips'];
        if (! in_array($gateway, $validGateways)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid gateway.',
            ], 400);
        }

        $invoiceNumber = $request->get('pid') ?? $request->get('purchase_order_id') ?? $request->get('oid');
        if (! $invoiceNumber) {
            Log::warning('Payment callback missing invoice reference', ['gateway' => $gateway]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid callback: missing invoice reference.',
            ], 400);
        }

        $sale = Sale::where('invoice_number', $invoiceNumber)->first();
        if (! $sale || ! $sale->company_id) {
            Log::warning('Payment callback sale not found', ['invoice_number' => $invoiceNumber, 'gateway' => $gateway]);
            return response()->json([
                'success' => false,
                'message' => 'Sale not found.',
            ], 404);
        }

        if ($sale->payment_status === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Payment already verified.',
                'data' => $sale->fresh(),
            ]);
        }

        $verified = false;

        switch ($gateway) {
            case 'esewa':
                $refId = $request->get('refId');
                if ($refId) {
                    $esewaConfig = config('services.esewa');
                    $verificationResponse = Http::post($esewaConfig['verification_url'] ?? 'https://uat.esewa.com.np/epay/transrec', [
                        'amt' => $sale->total_amount,
                        'rid' => $refId,
                        'pid' => $sale->invoice_number,
                        'scd' => $esewaConfig['merchant_code'] ?? '',
                    ]);
                    $verified = $verificationResponse->successful()
                        && str_contains($verificationResponse->body(), '<response_code>Success</response_code>');
                }
                break;

            case 'khalti':
                $token = $request->get('pidx');
                if ($token) {
                    $khaltiConfig = config('services.khalti');
                    $response = Http::withHeaders([
                        'Authorization' => 'Key '.($khaltiConfig['secret_key'] ?? ''),
                    ])->post($khaltiConfig['verify_url'] ?? 'https://a.khalti.com/api/v2/epayment/lookup/', [
                        'pidx' => $token,
                    ]);
                    $verified = $response->successful()
                        && $response->json('status') === 'Completed'
                        && abs((float) $response->json('total_amount') - (float) ($sale->total_amount * 100)) < 1;
                }
                break;

            case 'fonepay':
                $secretKey = config('services.fonepay.secret_key', '');
                $signature = $request->get('signature');
                if ($signature && $secretKey) {
                    $expected = strtoupper(hash_hmac('sha512', $request->get('PRN', ''), $secretKey));
                    $verified = hash_equals($expected, strtoupper($signature))
                        && ($request->get('status') === 'success' || $request->get('RC') === 'Successful');
                }
                break;

            case 'connectips':
                $secretKey = config('services.connectips.secret_key', '');
                $signature = $request->get('signature');
                if ($signature && $secretKey) {
                    $dataToVerify = $request->get('transaction_id', '') . $request->get('status', '') . $request->get('amount', '');
                    $expected = strtoupper(hash_hmac('sha256', $dataToVerify, $secretKey));
                    $verified = hash_equals($expected, strtoupper($signature));
                }
                break;
        }

        if ($verified) {
            DB::transaction(function () use ($sale) {
                $sale->update(['payment_status' => 'paid']);
            });

            Log::info('Payment verified successfully', [
                'gateway' => $gateway,
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully.',
                'data' => $sale->fresh(),
            ]);
        }

        Log::warning('Payment verification failed', [
            'gateway' => $gateway,
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'request_data' => $request->except(['secret', 'key', 'token']),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Payment verification failed.',
        ], 400);
    }
}
