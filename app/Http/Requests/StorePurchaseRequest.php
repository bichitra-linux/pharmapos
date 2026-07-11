<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class StorePurchaseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Purchase::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'supplier_invoice_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'expected_delivery' => 'nullable|date|after_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.batch_number' => 'required|string|max:100',
            'items.*.expiry_date' => 'required|date|after:today',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.mrp' => 'nullable|numeric|min:0',
            'items.*.selling_price' => 'nullable|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
