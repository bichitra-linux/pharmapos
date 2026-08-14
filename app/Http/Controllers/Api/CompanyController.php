<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CompanyController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $company = DB::table('companies')
            ->where('id', $request->user()->company_id)
            ->select([
                'id', 'name', 'slug', 'logo', 'address', 'city', 'state', 'country',
                'local_level', 'phone', 'phone_country_code', 'email',
                'pan_number', 'vat_number', 'registration_number',
                'drug_license_number', 'pharmacy_license_number',
                'pharmacist_name', 'pharmacist_registration_number',
                'subscription_plan_id', 'subscription_expires_at', 'settings',
                'google_maps_link', 'created_at', 'updated_at',
            ])
            ->first();

        if (! $company) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $company,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'local_level' => 'nullable|string|max:100',
            'phone_country_code' => 'nullable|string|max:10',
            'pan_number' => 'nullable|string|max:20',
            'vat_number' => 'nullable|string|max:20',
            'registration_number' => 'nullable|string|max:50',
            'drug_license_number' => 'nullable|string|max:50',
            'pharmacy_license_number' => 'nullable|string|max:50',
            'pharmacist_name' => 'nullable|string|max:255',
            'pharmacist_registration_number' => 'nullable|string|max:50',
            'google_maps_link' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimetypes:image/jpeg,image/png|max:2048',
        ]);

        $companyId = $request->user()->company_id;
        $updateData = $request->only([
            'name', 'email', 'phone', 'address', 'city', 'state', 'country',
            'local_level', 'phone_country_code', 'pan_number', 'vat_number',
            'registration_number', 'drug_license_number',
            'pharmacy_license_number', 'pharmacist_name',
            'pharmacist_registration_number', 'google_maps_link',
        ]);

        if ($request->hasFile('logo')) {
            $updateData['logo'] = $request->file('logo')->store('logos', 'public');
        }

        if ($request->has('name') && ! $request->has('slug')) {
            $updateData['slug'] = Str::slug($request->name) . '-' . Str::random(5);
        }

        $updateData['updated_at'] = now();

        DB::table('companies')
            ->where('id', $companyId)
            ->update($updateData);

        $company = DB::table('companies')
            ->where('id', $companyId)
            ->select([
                'id', 'name', 'slug', 'logo', 'address', 'city', 'state', 'country',
                'local_level', 'phone', 'phone_country_code', 'email',
                'pan_number', 'vat_number', 'registration_number',
                'drug_license_number', 'pharmacy_license_number',
                'pharmacist_name', 'pharmacist_registration_number',
                'subscription_plan_id', 'subscription_expires_at', 'settings',
                'google_maps_link', 'created_at', 'updated_at',
            ])
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Company profile updated successfully.',
            'data' => $company,
        ]);
    }
}
