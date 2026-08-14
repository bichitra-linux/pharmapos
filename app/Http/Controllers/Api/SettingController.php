<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $company = DB::table('companies')->where('id', $companyId)->first();
        $outlet = DB::table('outlets')->where('id', $request->user()->outlet_id)->first();

        $settings = [
            'company' => [
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'pan_number' => $company->pan_number,
                'vat_number' => $company->vat_number,
                'drug_license_number' => $company->drug_license_number,
                'pharmacy_license_number' => $company->pharmacy_license_number,
                'pharmacist_name' => $company->pharmacist_name,
                'pharmacist_registration_number' => $company->pharmacist_registration_number,
            ],
            'outlet' => $outlet ? [
                'name' => $outlet->name,
                'address' => $outlet->address,
                'phone' => $outlet->phone,
                'drug_license_number' => $outlet->drug_license_number,
            ] : null,
            'app_settings' => json_decode($company->settings ?? '{}', true),
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_prefix' => 'nullable|string|max:20',
            'invoice_footer' => 'nullable|string|max:500',
            'receipt_header' => 'nullable|string|max:500',
            'receipt_footer' => 'nullable|string|max:500',
            'prescription_prefix' => 'nullable|string|max:20',
            'purchase_prefix' => 'nullable|string|max:20',
            'return_prefix' => 'nullable|string|max:20',
            'receipt_printer_width' => 'nullable|integer|in:58,80',
            'default_payment_method' => 'nullable|string|max:50',
            'enable_loyalty' => 'nullable|boolean',
            'loyalty_rate' => 'nullable|numeric|min:0',
            'loyalty_points_per_rupee' => 'nullable|numeric|min:0',
            'low_stock_alert' => 'nullable|boolean',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'expiry_alert_days' => 'nullable|integer|min:1',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'default_tax_rate' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'enable_narcotics_register' => 'nullable|boolean',
            'date_format' => 'nullable|string|max:20',
            'time_zone' => 'nullable|string|max:50',
        ]);

        $companyId = $request->user()->company_id;
        $existingSettings = DB::table('companies')->where('id', $companyId)->value('settings');
        $settings = json_decode($existingSettings ?? '{}', true);

        $allowedKeys = [
            'invoice_prefix', 'invoice_footer', 'receipt_header', 'receipt_footer',
            'prescription_prefix', 'purchase_prefix', 'return_prefix',
            'receipt_printer_width', 'default_payment_method', 'enable_loyalty',
            'loyalty_rate', 'loyalty_points_per_rupee', 'low_stock_alert',
            'low_stock_threshold', 'expiry_alert_days', 'vat_rate',
            'default_tax_rate', 'currency', 'currency_symbol',
            'enable_narcotics_register', 'date_format', 'time_zone',
        ];

        foreach ($allowedKeys as $key) {
            if ($request->has($key)) {
                $settings[$key] = $request->input($key);
            }
        }

        DB::table('companies')
            ->where('id', $companyId)
            ->update([
                'settings' => json_encode($settings),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data' => $settings,
        ]);
    }
}
