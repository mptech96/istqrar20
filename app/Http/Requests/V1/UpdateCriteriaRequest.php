<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCriteriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Criteria data
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['sometimes', 'string', 'max:100'],
            'weight' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            
            // Criteria ranges array
            'ranges' => ['sometimes', 'array', 'min:1'],
            'ranges.*.name' => ['required', 'string', 'max:100'],
            'ranges.*.min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'ranges.*.max_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'ranges.*.color' => ['nullable', 'string', 'max:10', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'ranges.*.description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'weight.max' => 'الوزن يجب أن يكون بين 0 و 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المعيار',
            'category' => 'الفئة',
            'weight' => 'الوزن',
        ];
    }
}
