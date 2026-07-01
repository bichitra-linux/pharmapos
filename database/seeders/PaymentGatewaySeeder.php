<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'code' => 'esewa',
                'name' => 'eSewa',
                'is_active' => false,
                'is_sandbox' => true,
                'config' => [
                    'merchant_code' => '',
                    'api_url' => 'https://uat.esewa.com.np/epay/main',
                    'verify_url' => 'https://uat.esewa.com.np/epay/transrec',
                    'success_url' => '/api/payments/callback/esewa',
                    'failure_url' => '/api/payments/callback/esewa',
                ],
            ],
            [
                'code' => 'khalti',
                'name' => 'Khalti',
                'is_active' => false,
                'is_sandbox' => true,
                'config' => [
                    'secret_key' => '',
                    'api_url' => 'https://a.khalti.com/api/v2/epayment/initiate/',
                    'verify_url' => 'https://a.khalti.com/api/v2/epayment/lookup/',
                    'return_url' => '/api/payments/callback/khalti',
                ],
            ],
            [
                'code' => 'fonepay',
                'name' => 'Fonepay',
                'is_active' => false,
                'is_sandbox' => true,
                'config' => [
                    'merchant_id' => '',
                    'secret_key' => '',
                    'payment_url' => 'https://dev-client.fonepay.com/api/merchantRequest',
                    'verify_url' => 'https://dev-client.fonepay.com/api/merchantRequest/verification',
                    'return_url' => '/api/payments/callback/fonepay',
                ],
            ],
            [
                'code' => 'connectips',
                'name' => 'ConnectIPS',
                'is_active' => false,
                'is_sandbox' => true,
                'config' => [
                    'merchant_id' => '',
                    'secret_key' => '',
                    'payment_url' => 'https://uat.connectips.com.np/payment/page',
                    'return_url' => '/api/payments/callback/connectips',
                ],
            ],
            [
                'code' => 'ime_pay',
                'name' => 'IME Pay',
                'is_active' => false,
                'is_sandbox' => true,
                'config' => [
                    'merchant_code' => '',
                    'secret_key' => '',
                    'api_url' => 'https://stg.imepay.com.np:7979/api/WebV1/',
                    'return_url' => '/api/payments/callback/imepay',
                ],
            ],
        ];

        foreach ($gateways as $gateway) {
            $gateway['config'] = json_encode($gateway['config']);
            DB::table('payment_gateways')->updateOrInsert(
                ['code' => $gateway['code']],
                $gateway
            );
        }
    }
}
