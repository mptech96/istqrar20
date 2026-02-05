<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answer_value' => ['sometimes'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],
            'auditor_comment' => ['nullable', 'string'],
            'corrective_action' => ['nullable', 'string'],
            'corrective_deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'correction_status' => ['sometimes', 'in:pending,in_progress,completed,verified,waived'],
            'verified_by' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'correction_status.in' => 'حالة التصحيح غير صالحة.',
            'corrective_deadline.after_or_equal' => 'يجب أن يكون تاريخ التصحيح اليوم أو بعده.',
        ];
    }

    public function attributes(): array
    {
        return [
            'correction_status' => 'حالة التصحيح',
            'corrective_deadline' => 'موعد التصحيح',
        ];
    }
}
