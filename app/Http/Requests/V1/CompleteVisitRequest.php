<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CompleteVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auditor_notes' => ['nullable', 'string'],
            'total_score' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'category' => ['nullable', 'string', 'in:A,B,C,D,F'],
        ];
    }

    public function messages(): array
    {
        return [
            'percentage.max' => 'النسبة المئوية يجب أن تكون بين 0 و 100.',
            'category.in' => 'الفئة غير صالحة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'total_score' => 'الدرجة الكلية',
            'percentage' => 'النسبة المئوية',
            'category' => 'الفئة',
        ];
    }
}
