<?php

namespace App\Http\Requests\Fmcg;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProcessMappingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mapping' => ['required', 'array'],
            'mapping.sku' => ['required', 'string'],
            'mapping.quantity' => ['required', 'string'],
            'mapping.requested_date' => ['nullable', 'string'],
            'mapping.note' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'mapping.sku.required' => 'The SKU field must be mapped to a CSV column.',
            'mapping.quantity.required' => 'The Quantity field must be mapped to a CSV column.',
        ];
    }
}
