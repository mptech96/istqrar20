<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['prohibited'],
            'scheduled_date' => ['sometimes', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'estimated_duration' => ['nullable', 'integer', 'min:1', 'max:480'],
            'auditor_notes' => ['nullable', 'string'],
            'branch_feedback' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            //'total_score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            //'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            //'category' => ['nullable', 'string', 'in:A,B,C,D,F'],
            //'approved_by' => ['nullable', 'exists:users,id'],
            'rejection_reason' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.prohibited' => 'لا يمكن تغيير حالة الزيارة من خلال طلب التحديث العام. استخدم إجراء بدء الزيارة أو إنهائها أو رفضها المخصص.',
            'scheduled_date.date' => 'حقل التاريخ المجدول يجب أن يكون تاريخاً صحيحاً.',
            
            'scheduled_time.date_format' => 'صيغة الوقت المجدول غير صحيحة. يجب أن تكون HH:MM (مثال: 14:30).',
            
            'estimated_duration.integer' => 'حقل المدة التقديرية يجب أن يكون رقماً صحيحاً.',
            'estimated_duration.min' => 'المدة التقديرية يجب أن تكون دقيقة واحدة على الأقل.',
            'estimated_duration.max' => 'المدة التقديرية لا تتجاوز 480 دقيقة (8 ساعات).',
            
            'auditor_notes.string' => 'حقل ملاحظات المدقق يجب أن يكون نصاً.',
            
            'branch_feedback.string' => 'حقل تقييم الفرع يجب أن يكون نصاً.',
            
            'internal_notes.string' => 'حقل الملاحظات الداخلية يجب أن يكون نصاً.',
            
            
            'rejection_reason.string' => 'حقل سبب الرفض يجب أن يكون نصاً.',
            
            // رسائل الحقول المُعلقة - يمكن تفعيلها لاحقاً
            // 'total_score.numeric' => 'حقل الدرجة الإجمالية يجب أن يكون رقماً.',
            // 'total_score.min' => 'الدرجة الإجمالية يجب أن تكون 0 على الأقل.',
            // 'total_score.max' => 'الدرجة الإجمالية لا تتجاوز 1000.',
            
            // 'percentage.numeric' => 'حقل النسبة المئوية يجب أن يكون رقماً.',
            // 'percentage.min' => 'النسبة المئوية يجب أن تكون 0 على الأقل.',
            // 'percentage.max' => 'النسبة المئوية لا تتجاوز 100.',
            
            // 'category.in' => 'التصنيف يجب أن يكون أحد: A, B, C, D, F.',
            
            // 'approved_by.exists' => 'المعتمد المحدد غير موجود.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'حالة الزيارة',
            'scheduled_date' => 'التاريخ المجدول',
            'scheduled_time' => 'الوقت المجدول',
            'estimated_duration' => 'المدة التقديرية',
            'auditor_notes' => 'ملاحظات المدقق',
            'branch_feedback' => 'تقييم الفرع',
            'internal_notes' => 'الملاحظات الداخلية',
            'rejection_reason' => 'سبب الرفض',
            // 'total_score' => 'الدرجة الإجمالية',
            // 'percentage' => 'النسبة المئوية',
            // 'category' => 'التصنيف',
            // 'approved_by' => 'المعتمد',
        ];
    }
}
