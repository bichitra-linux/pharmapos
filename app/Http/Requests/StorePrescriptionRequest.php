<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Prescription;

final class StorePrescriptionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Prescription::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'doctor_name' => 'nullable|string|max:255',
            'hospital_name' => 'nullable|string|max:255',
            'prescription_date' => 'nullable|date',
            'diagnosis' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimetypes:image/jpeg,image/png,application/pdf|max:5120',
            'items' => 'nullable|array',
            'items.*.medicine_id' => 'nullable|exists:medicines,id',
            'items.*.medicine_name' => 'nullable|string|max:255',
            'items.*.dosage' => 'nullable|string|max:100',
            'items.*.frequency' => 'nullable|string|max:100',
            'items.*.duration' => 'nullable|string|max:100',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }
}
