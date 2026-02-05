<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'auditor_id' => ['required', 'exists:users,id'],
            'checklist_id' => ['required', 'exists:checklists,id'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'estimated_duration' => ['nullable', 'integer', 'min:1', 'max:480'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'الفرع مطلوب.',
            'auditor_id.required' => 'المراقب مطلوب.',
            'checklist_id.required' => 'قائمة المراجعة مطلوبة.',
            'scheduled_date.after_or_equal' => 'يجب أن يكون التاريخ المحدد اليوم أو بعده.',
            'estimated_duration.max' => 'المدة المقدرة يجب أن تكون أقل من 8 ساعات (480 دقيقة).',
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_id' => 'الفرع',
            'auditor_id' => 'المراقب',
            'checklist_id' => 'قائمة المراجعة',
            'scheduled_date' => 'التاريخ المحدد',
            'estimated_duration' => 'المدة المقدرة',
        ];
    }
}