<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StartVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'visit_date.before_or_equal' => 'تاريخ الزيارة يجب أن يكون اليوم أو تاريخ سابق.',
        ];
    }

    public function attributes(): array
    {
        return [
            'visit_date' => 'تاريخ الزيارة',
        ];
    }
}
