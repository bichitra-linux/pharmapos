<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentGatewayService
{
    /**
     * Initiate eSewa payment.
     */
    public function initiateEsewa(float $amount, string $productId, string $successUrl, string $failureUrl): array
    {
        $config = $this->getConfig('esewa');

        $payload = [
            'amt' => $amount,
            'pdc' => 0,
            'psc' => 0,
            'txAmt' => 0,
            'tAmt' => $amount,
            'pid' => $productId,
            'scd' => $config['merchant_code'],
            'su' => $successUrl,
            'fu' => $failureUrl,
        ];

        return [
            'gateway' => 'esewa',
            'url' => $config['payment_url'],
            'payload' => $payload,
            'method' => 'POST',
        ];
    }

    /**
     * Verify eSewa payment.
     */
    public function verifyEsewa(string $refId, string $productId, float $amount): array
    {
        $config = $this->getConfig('esewa');

        $response = Http::get($config['verify_url'], [
            'amt' => $amount,
            'rid' => $refId,
            'pid' => $productId,
            'scd' => $config['merchant_code'],
        ]);

        if ($response->successful() && str_contains($response->body(), 'Success')) {
            return [
                'success' => true,
                'transaction_id' => $refId,
                'amount' => $amount,
                'gateway' => 'esewa',
            ];
        }

        Log::error('eSewa verification failed', ['response' => $response->body()]);

        return [
            'success' => false,
            'message' => 'Payment verification failed',
            'gateway' => 'esewa',
        ];
    }

    /**
     * Initiate Khalti payment.
     */
    public function initiateKhalti(float $amount, string $purchaseOrderId, string $productName, string $returnUrl): array
    {
        $config = $this->getConfig('khalti');

        $response = Http::withHeaders([
            'Authorization' => 'Key '.$config['secret_key'],
        ])->post($config['initiation_url'], [
            'return_url' => $returnUrl,
            'website_url' => config('app.url'),
            'amount' => (int) ($amount * 100), // Khalti uses paisa
            'purchase_order_id' => $purchaseOrderId,
            'purchase_order_name' => $productName,
            'customer_info' => [
                'name' => 'Customer',
            ],
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'gateway' => 'khalti',
                'payment_url' => $response->json('payment_url'),
                'pidx' => $response->json('pidx'),
            ];
        }

        Log::error('Khalti initiation failed', ['response' => $response->body()]);

        throw new RuntimeException('Failed to initiate Khalti payment');
    }

    /**
     * Verify Khalti payment.
     */
    public function verifyKhalti(string $pidx): array
    {
        $config = $this->getConfig('khalti');

        $response = Http::withHeaders([
            'Authorization' => 'Key '.$config['secret_key'],
        ])->post($config['verify_url'], [
            'pidx' => $pidx,
        ]);

        if ($response->successful() && $response->json('status') === 'Completed') {
            return [
                'success' => true,
                'transaction_id' => $response->json('transaction_id'),
                'amount' => $response->json('total_amount') / 100,
                'gateway' => 'khalti',
            ];
        }

        Log::error('Khalti verification failed', ['response' => $response->body()]);

        return [
            'success' => false,
            'message' => 'Payment verification failed',
            'gateway' => 'khalti',
        ];
    }

    /**
     * Initiate Fonepay payment.
     */
    public function initiateFonepay(float $amount, string $productReference, string $remarks): array
    {
        $config = $this->getConfig('fonepay');

        $data = [
            'PID' => $config['merchant_id'],
            'MD' => 'P',
            'AMT' => number_format($amount, 2, '.', ''),
            'CRN' => 'NPR',
            'DT' => now()->format('d/m/Y'),
            'R1' => $remarks,
            'R2' => $productReference,
            'RU' => config('app.url').'/payment/fonepay/callback',
        ];

        $signatureString = implode(',', $data).','.$config['secret_key'];
        $data['DV'] = hash_hmac('sha512', $signatureString, $config['secret_key']);

        return [
            'gateway' => 'fonepay',
            'url' => $config['payment_url'],
            'payload' => $data,
            'method' => 'POST',
        ];
    }

    /**
     * Verify Fonepay payment.
     */
    public function verifyFonepay(array $responseData): array
    {
        $config = $this->getConfig('fonepay');

        $verificationData = [
            'PID' => $config['merchant_id'],
            'PRN' => $responseData['PRN'] ?? '',
            'BID' => $responseData['BID'] ?? '',
            'AMT' => $responseData['AMT'] ?? '',
        ];

        $signatureString = implode(',', $verificationData).','.$config['secret_key'];
        $verificationData['DV'] = hash_hmac('sha512', $signatureString, $config['secret_key']);

        $response = Http::post($config['verify_url'], $verificationData);

        if ($response->successful()) {
            $body = $response->body();
            if (str_contains($body, 'Success') || str_contains($body, 'true')) {
                return [
                    'success' => true,
                    'transaction_id' => $responseData['PRN'] ?? '',
                    'gateway' => 'fonepay',
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Payment verification failed',
            'gateway' => 'fonepay',
        ];
    }

    /**
     * Initiate ConnectIPS payment.
     */
    public function initiateConnectIps(float $amount, string $referenceId, string $remarks): array
    {
        $config = $this->getConfig('connectips');

        $data = [
            'MERCHANTID' => $config['merchant_id'],
            'APPID' => $config['app_id'],
            'APPNAME' => $config['app_name'],
            'TXNID' => $referenceId,
            'TXNDATE' => now()->format('d/m/Y'),
            'TXNCRNCY' => 'NPR',
            'TXNAMT' => (int) ($amount * 100),
            'REFERENCEID' => $referenceId,
            'REMARKS' => $remarks,
            'PARTICULARS' => $remarks,
            'SUCCESS_URL' => config('app.url').'/payment/connectips/success',
            'FAILURE_URL' => config('app.url').'/payment/connectips/failure',
        ];

        $signatureData = $data['MERCHANTID'].','.$data['APPID'].','.$data['TXNID'].','.$data['TXNDATE'].','.$data['TXNCRNCY'].','.$data['TXNAMT'];
        $data['HASH'] = hash_hmac('sha256', $signatureData, $config['secret_key']);

        return [
            'gateway' => 'connectips',
            'url' => $config['payment_url'],
            'payload' => $data,
            'method' => 'POST',
        ];
    }

    /**
     * Verify ConnectIPS payment.
     */
    public function verifyConnectIps(array $responseData): array
    {
        $config = $this->getConfig('connectips');

        $signatureData = $responseData['MERCHANTID'].','.$responseData['APPID'].','.$responseData['TXNID'].','.$responseData['TXNDATE'].','.$responseData['TXNCRNCY'].','.$responseData['TXNAMT'];
        $expectedHash = hash_hmac('sha256', $signatureData, $config['secret_key']);

        if (($responseData['HASH'] ?? '') === $expectedHash) {
            return [
                'success' => true,
                'transaction_id' => $responseData['TXNID'] ?? '',
                'amount' => ((int) ($responseData['TXNAMT'] ?? 0)) / 100,
                'gateway' => 'connectips',
            ];
        }

        return [
            'success' => false,
            'message' => 'Payment verification failed',
            'gateway' => 'connectips',
        ];
    }

    /**
     * Get gateway configuration from config file.
     */
    private function getConfig(string $gateway): array
    {
        $config = config("payment.gateways.{$gateway}");

        if (! $config) {
            throw new RuntimeException("Payment gateway configuration not found: {$gateway}");
        }

        return $config;
    }
}
