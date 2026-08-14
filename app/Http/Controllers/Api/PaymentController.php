<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PaymentController extends Controller
{
    public function initiate(Request $request, string $gateway, PaymentGatewayService $paymentGateway): JsonResponse
    {
        $validGateways = ['esewa', 'khalti', 'fonepay', 'connectips'];
        if (! in_array($gateway, $validGateways)) {
            return $this->error('Invalid gateway.', 400);
        }

        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $sale = Sale::findOrFail($request->sale_id);

        $result = $paymentGateway->initiate($gateway, $sale);

        if (! $result['success']) {
            return $this->error($result['message'] ?? 'Failed to initiate payment.', 400);
        }

        return $this->success($result['data']);
    }

    public function callback(Request $request, string $gateway, PaymentGatewayService $paymentGateway): JsonResponse
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

        $result = $paymentGateway->verify($gateway, $request->all(), (float) $sale->total_amount);

        if ($result['success']) {
            $verifiedAmount = $result['amount'] ?? null;

            if ($verifiedAmount === null || abs((float) $verifiedAmount - (float) $sale->total_amount) > 0.01) {
                Log::warning('Payment amount mismatch', [
                    'gateway' => $gateway,
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'expected_amount' => $sale->total_amount,
                    'received_amount' => $verifiedAmount,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount mismatch.',
                ], 400);
            }

            DB::transaction(function () use ($sale, $result) {
                $sale->update([
                    'payment_status' => 'paid',
                    'transaction_id' => $result['transaction_id'] ?? null,
                ]);
            });

            Log::info('Payment verified successfully', [
                'gateway' => $gateway,
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'transaction_id' => $result['transaction_id'] ?? null,
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
