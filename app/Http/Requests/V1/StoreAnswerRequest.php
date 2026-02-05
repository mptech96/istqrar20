<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_id' => ['required', 'exists:visits,id'],
            'question_id' => ['required', 'exists:questions,id'],
            'answer_value' => ['required'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],
            'auditor_comment' => ['nullable', 'string'],
            'corrective_action' => ['nullable', 'string'],
            'corrective_deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'visit_id.exists' => 'الزيارة غير موجودة.',
            'question_id.exists' => 'السؤال غير موجود.',
            'corrective_deadline.after_or_equal' => 'يجب أن يكون تاريخ التصحيح اليوم أو بعده.',
        ];
    }

    public function attributes(): array
    {
        return [
            'visit_id' => 'الزيارة',
            'question_id' => 'السؤال',
            'answer_value' => 'قيمة الإجابة',
            'corrective_deadline' => 'موعد التصحيح',
        ];
    }
}
