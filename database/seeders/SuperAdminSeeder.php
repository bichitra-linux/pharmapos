<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('super_admins')->insert([
            'name' => 'Super Admin',
            'email' => 'superadmin@pharmpos.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Default platform settings
        $settings = [
            ['key' => 'platform_name', 'value' => 'PharmaPOS', 'group' => 'general'],
            ['key' => 'platform_email', 'value' => 'support@pharmpos.com', 'group' => 'general'],
            ['key' => 'platform_phone', 'value' => '+977-1-XXXXXXX', 'group' => 'general'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'group' => 'maintenance'],
            ['key' => 'maintenance_message', 'value' => 'System is under maintenance. Please try again later.', 'group' => 'maintenance'],
            ['key' => 'default_currency', 'value' => 'NPR', 'group' => 'general'],
            ['key' => 'default_currency_symbol', 'value' => 'रू', 'group' => 'general'],
            ['key' => 'default_timezone', 'value' => 'Asia/Kathmandu', 'group' => 'general'],
            ['key' => 'smtp_host', 'value' => '', 'group' => 'email'],
            ['key' => 'smtp_port', 'value' => '587', 'group' => 'email'],
            ['key' => 'smtp_username', 'value' => '', 'group' => 'email'],
            ['key' => 'smtp_password', 'value' => '', 'group' => 'email'],
            ['key' => 'sms_gateway', 'value' => '', 'group' => 'sms'],
            ['key' => 'sms_api_key', 'value' => '', 'group' => 'sms'],
            ['key' => 'trial_days', 'value' => '14', 'group' => 'billing'],
            ['key' => 'grace_period_days', 'value' => '7', 'group' => 'billing'],
        ];

        $now = now();
        foreach ($settings as $setting) {
            DB::table('platform_settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'group' => $setting['group'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
