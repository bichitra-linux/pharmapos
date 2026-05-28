<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        // Default payment methods are created per-company via CompanyCreated event
        // This seeder documents the default methods that should be created:
        // - Cash (type: cash)
        // - eSewa (type: digital_wallet, gateway: esewa)
        // - Khalti (type: digital_wallet, gateway: khalti)
        // - IME Pay (type: digital_wallet, gateway: ime_pay)
        // - Fonepay (type: digital_wallet, gateway: fonepay)
        // - ConnectIPS (type: bank_transfer, gateway: connectips)
        // - Card (type: card)
        // - Bank Transfer (type: bank_transfer)
    }
}
