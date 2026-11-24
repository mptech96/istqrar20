<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class BulkAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'exists:questions,id', 'distinct'],
            'answers.*.answer_value' => ['required'],
            'answers.*.score' => ['nullable', 'numeric', 'min:0'],
            'answers.*.comment' => ['nullable', 'string'],
            'answers.*.auditor_comment' => ['nullable', 'string'],
            'answers.*.corrective_action' => ['nullable', 'string'],
            'answers.*.corrective_deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'answers.*.attachments' => ['nullable', 'array', 'max:10'],
            'answers.*.attachments.*' => ['file', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'يجب إضافة إجابات.',
            'answers.*.question_id.required' => 'معرف السؤال مطلوب.',
            'answers.*.question_id.exists' => 'السؤال غير موجود.',
            'answers.*.answer_value.required' => 'قيمة الإجابة مطلوبة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'answers' => 'الإجابات',
            'answers.*.question_id' => 'معرف السؤال',
            'answers.*.answer_value' => 'قيمة الإجابة',
        ];
    }
}
