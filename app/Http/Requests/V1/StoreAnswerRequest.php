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
            'question_id' => ['required', 'exists:questions,id'],
            'answer_value' => ['required'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],
            'auditor_comment' => ['nullable', 'string'],
            'corrective_action' => ['nullable', 'string'],
            'corrective_deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.required' => 'حقل معرف السؤال مطلوب.',
            'question_id.exists' => 'السؤال المحدد غير موجود.',
            
            'answer_value.required' => 'حقل قيمة الإجابة مطلوب.',
            
            'score.numeric' => 'حقل الدرجة يجب أن يكون رقماً.',
            'score.min' => 'الدرجة يجب أن تكون 0 على الأقل.',
            
            'comment.string' => 'حقل التعليق يجب أن يكون نصاً.',
            
            'auditor_comment.string' => 'حقل تعليق المدقق يجب أن يكون نصاً.',
            
            'corrective_action.string' => 'حقل الإجراء التصحيحي يجب أن يكون نصاً.',
            
            'corrective_deadline.date' => 'حقل الموعد النهائي للإجراء التصحيحي يجب أن يكون تاريخاً صحيحاً.',
            'corrective_deadline.after_or_equal' => 'الموعد النهائي للإجراء التصحيحي يجب أن يكون اليوم أو تاريخ لاحق.',
        ];
    }

    public function attributes(): array
    {
        return [
            'question_id' => 'معرف السؤال',
            'answer_value' => 'قيمة الإجابة',
            'score' => 'الدرجة',
            'comment' => 'تعليق',
            'auditor_comment' => 'تعليق المدقق',
            'corrective_action' => 'الإجراء التصحيحي',
            'corrective_deadline' => 'الموعد النهائي للإجراء التصحيحي',
        ];
    }
}
