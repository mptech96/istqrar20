<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class RejectVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'سبب الرفض مطلوب.',
            'rejection_reason.min' => 'سبب الرفض يجب أن يكون على الأقل 10 أحرف.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rejection_reason' => 'سبب الرفض',
        ];
    }
}
