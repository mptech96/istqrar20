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
            'user_type.required' => 'حقل نوع المستخدم مطلوب.',
            'user_type.in' => 'نوع المستخدم يجب أن يكون أحد: شركة، فرع، مدقق، مشرف، جودة.',
            
            'company_id.exists' => 'الشركة المحددة غير موجودة.',
            
            'branch_id.exists' => 'الفرع المحدد غير موجود.',
            
            'visit_id.exists' => 'الزيارة المحددة غير موجودة.',
            
            'subject.required' => 'حقل الموضوع مطلوب.',
            'subject.string' => 'حقل الموضوع يجب أن يكون نصاً.',
            'subject.max' => 'حقل الموضوع لا يتجاوز 255 حرفاً.',
            
            'description.required' => 'حقل الوصف مطلوب.',
            'description.string' => 'حقل الوصف يجب أن يكون نصاً.',
            
            'ticket_type.required' => 'حقل نوع التذكرة مطلوب.',
            'ticket_type.in' => 'نوع التذكرة يجب أن يكون أحد: نزاع، شكوى، طلب، استفسار، فني، ملاحظات.',
            
            'priority.in' => 'الأولوية يجب أن تكون أحد: منخفضة، متوسطة، عالية، حرجة.',
            
            'department.in' => 'القسم يجب أن يكون أحد: دعم، فني، جودة، فوترة، مشرفين.',
            
            'attachments.array' => 'حقل المرفقات يجب أن يكون مصفوفة.',
            
            'attachments.*.file' => 'كل مرفق يجب أن يكون ملفاً.',
            'attachments.*.max' => 'حجم الملف لا يتجاوز 10 ميجابايت.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_type' => 'نوع المستخدم',
            'company_id' => 'الشركة',
            'branch_id' => 'الفرع',
            'visit_id' => 'الزيارة',
            'subject' => 'الموضوع',
            'description' => 'الوصف',
            'ticket_type' => 'نوع التذكرة',
            'priority' => 'الأولوية',
            'department' => 'القسم',
            'attachments' => 'المرفقات',
            'attachments.*' => 'الملف المرفق',
        ];
    }
}
