<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'department' => ['sometimes', Rule::in(['support', 'technical', 'quality', 'billing', 'admin'])],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['open', 'in_progress', 'pending_customer', 'resolved', 'closed', 'escalated'])],
            'resolution' => ['nullable', 'string', 'required_if:status,resolved,closed'],
            'customer_satisfaction' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.max' => 'عنوان التذكرة يجب ألا يتجاوز 255 حرف',
            'priority.in' => 'الأولوية غير صالحة',
            'department.in' => 'القسم غير صالح',
            'assigned_to.exists' => 'المستخدم غير موجود',
            'status.in' => 'الحالة غير صالحة',
            'resolution.required_if' => 'الحل مطلوب عند إغلاق التذكرة',
            'customer_satisfaction.min' => 'التقييم يجب أن يكون بين 1 و 5',
            'customer_satisfaction.max' => 'التقييم يجب أن يكون بين 1 و 5',
        ];
    }
}
