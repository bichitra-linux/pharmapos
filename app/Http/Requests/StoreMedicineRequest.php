<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:medicine_categories,id',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'salt_composition_id' => 'nullable|exists:salt_compositions,id',
            'hsn_code' => 'nullable|string|max:20',
            'barcode' => 'nullable|string|max:50',
            'schedule' => 'nullable|in:otc,h,h1,x,g',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'pack_size' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:20',
            'mrp' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|numeric|min:0|max:100',
            'is_vat_applicable' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:2000',
            'side_effects' => 'nullable|string|max:2000',
            'storage_instructions' => 'nullable|string|max:500',
        ];
    }
}
