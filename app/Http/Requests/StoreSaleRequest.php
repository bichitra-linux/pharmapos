<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StoreSaleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Sale::class) ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'customer_id' => 'nullable|exists:customers,id',
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'prescription_verified' => 'nullable|boolean',
            'sale_type' => 'nullable|in:walk_in,online,delivery',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => ['required', Rule::exists('medicines', 'id')->where('company_id', $companyId)],
            'items.*.batch_id' => ['nullable', Rule::exists('medicine_batches', 'id')->where('company_id', $companyId)],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.sell_mode' => 'nullable|in:pack,piece',
            'items.*.units_per_pack' => 'nullable|integer|min:1',
            'items.*.pieces_quantity' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('company_id', $companyId)],
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference_number' => 'nullable|string|max:100',
            'payment_method_id' => ['nullable', Rule::exists('payment_methods', 'id')->where('company_id', $companyId)],
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
