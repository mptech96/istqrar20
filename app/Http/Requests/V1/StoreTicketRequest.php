<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'user_type' => ['required', Rule::in(['company', 'branch', 'auditor', 'admin', 'quality'])],
            'company_id' => ['nullable', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'visit_id' => ['nullable', 'exists:visits,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'ticket_type' => ['required', Rule::in(['dispute', 'complaint', 'request', 'inquiry', 'technical', 'feedback'])],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'department' => ['sometimes', Rule::in(['support', 'technical', 'quality', 'billing', 'admin'])],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'], // 10MB max per file
        ];
    }

    public function messages(): array
    {
        return [
            'user_type.required' => 'نوع المستخدم مطلوب',
            'user_type.in' => 'نوع المستخدم غير صالح',
            'company_id.exists' => 'الشركة غير موجودة',
            'branch_id.exists' => 'الفرع غير موجود',
            'visit_id.exists' => 'الزيارة غير موجودة',
            'subject.required' => 'عنوان التذكرة مطلوب',
            'subject.max' => 'عنوان التذكرة يجب ألا يتجاوز 255 حرف',
            'description.required' => 'وصف التذكرة مطلوب',
            'ticket_type.required' => 'نوع التذكرة مطلوب',
            'ticket_type.in' => 'نوع التذكرة غير صالح',
            'priority.in' => 'الأولوية غير صالحة',
            'department.in' => 'القسم غير صالح',
            'attachments.*.max' => 'الملف كبير جداً (الحد الأقصى 10 ميجابايت)',
        ];
    }
}
