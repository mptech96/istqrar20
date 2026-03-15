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
            'branch_id.required' => 'حقل معرف الفرع مطلوب.',
            'branch_id.exists' => 'الفرع المحدد غير موجود.',
            
            'auditor_id.required' => 'حقل معرف المدقق مطلوب.',
            'auditor_id.exists' => 'المدقق المحدد غير موجود.',
            
            'checklist_id.required' => 'حقل معرف قائمة التحقق مطلوب.',
            'checklist_id.exists' => 'قائمة التحقق المحددة غير موجودة.',
            
            'scheduled_date.required' => 'حقل التاريخ المجدول مطلوب.',
            'scheduled_date.date' => 'حقل التاريخ المجدول يجب أن يكون تاريخاً صحيحاً.',
            'scheduled_date.after_or_equal' => 'التاريخ المجدول يجب أن يكون اليوم أو تاريخ لاحق.',
            
            'scheduled_time.date_format' => 'صيغة الوقت المجدول غير صحيحة. يجب أن تكون HH:MM (مثال: 14:30).',
            
            'estimated_duration.integer' => 'حقل المدة التقديرية يجب أن يكون رقماً صحيحاً.',
            'estimated_duration.min' => 'المدة التقديرية يجب أن تكون دقيقة واحدة على الأقل.',
            'estimated_duration.max' => 'المدة التقديرية لا تتجاوز 480 دقيقة (8 ساعات).',
            
            'internal_notes.string' => 'حقل الملاحظات الداخلية يجب أن يكون نصاً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_id' => 'معرف الفرع',
            'auditor_id' => 'معرف المدقق',
            'checklist_id' => 'معرف قائمة التحقق',
            'scheduled_date' => 'التاريخ المجدول',
            'scheduled_time' => 'الوقت المجدول',
            'estimated_duration' => 'المدة التقديرية (بالدقائق)',
            'internal_notes' => 'الملاحظات الداخلية',
        ];
    }
}