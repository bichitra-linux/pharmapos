<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'pan_number' => 'nullable|string|max:20',
            'drug_license_number' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|integer|min:0|max:365',
            'is_active' => 'nullable|boolean',
        ];
    }
}
