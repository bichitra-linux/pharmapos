<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        $saleId = $request->get('sale_id') ?? $request->get('oid');

        if (! $saleId) {
            // Try to find by invoice number
            $invoiceNumber = $request->get('pid') ?? $request->get('purchase_order_id');
            if ($invoiceNumber) {
                $sale = Sale::where('invoice_number', $invoiceNumber)->first();
                if ($sale) {
                    $saleId = $sale->id;
                }
            }
        }

        if (! $saleId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid callback: missing sale reference.',
            ], 400);
        }

        $sale = Sale::find($saleId);
        if (! $sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found.',
            ], 404);
        }

        $verified = false;

        switch ($gateway) {
            case 'esewa':
                $refId = $request->get('refId');
                $verified = ! empty($refId);
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
                    $verified = $response->successful() && $response->json('status') === 'Completed';
                }
                break;

            case 'fonepay':
            case 'connectips':
                $verified = $request->get('status') === 'success' || $request->get('RC') === 'Successful';
                break;
        }

        if ($verified) {
            $sale->update([
                'payment_status' => 'paid',
                'payment_reference' => $request->get('refId') ?? $request->get('pidx') ?? $request->get('transaction_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully.',
                'data' => $sale->fresh(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment verification failed.',
        ], 400);
    }
}
