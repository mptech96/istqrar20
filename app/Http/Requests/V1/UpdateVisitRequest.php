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
            'scheduled_date' => ['sometimes', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'estimated_duration' => ['nullable', 'integer', 'min:1', 'max:480'],
            'auditor_notes' => ['nullable', 'string'],
            'branch_feedback' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:draft,scheduled,in_progress,completed,cancelled,under_review,approved,rejected'],
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
            'status.in' => 'حالة الزيارة غير صالحة.',
            'category.in' => 'الفئة غير صالحة.',
            'percentage.max' => 'النسبة المئوية يجب أن تكون بين 0 و 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'حالة الزيارة',
            'total_score' => 'الدرجة الكلية',
            'percentage' => 'النسبة المئوية',
            'category' => 'الفئة',
        ];
    }
}
