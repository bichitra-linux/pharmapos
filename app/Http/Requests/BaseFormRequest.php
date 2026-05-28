<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user() && $this->user()->company_id) {
            $this->merge([
                'company_id' => $this->user()->company_id,
            ]);
        }
    }
}
