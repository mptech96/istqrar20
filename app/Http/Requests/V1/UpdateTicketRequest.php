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
            'subject.string' => 'حقل الموضوع يجب أن يكون نصاً.',
            'subject.max' => 'حقل الموضوع لا يتجاوز 255 حرفاً.',
            
            'description.string' => 'حقل الوصف يجب أن يكون نصاً.',
            
            'priority.in' => 'الأولوية يجب أن تكون أحد: منخفضة، متوسطة، عالية، حرجة.',
            
            'department.in' => 'القسم يجب أن يكون أحد: دعم، فني، جودة، فوترة، مشرفين.',
            
            'assigned_to.exists' => 'المستخدم المعين غير موجود.',
            
            'status.in' => 'الحالة يجب أن تكون أحد: مفتوحة، قيد التنفيذ، في انتظار العميل، محلولة، مغلقة، مُصعدة.',
            
            'resolution.string' => 'حقل الحل يجب أن يكون نصاً.',
            'resolution.required_if' => 'حقل الحل مطلوب عندما تكون الحالة "محلولة" أو "مغلقة".',
            
            'customer_satisfaction.integer' => 'حقل تقييم رضا العميل يجب أن يكون رقماً صحيحاً.',
            'customer_satisfaction.min' => 'تقييم رضا العميل يجب أن يكون 1 على الأقل.',
            'customer_satisfaction.max' => 'تقييم رضا العميل لا يتجاوز 5.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'الموضوع',
            'description' => 'الوصف',
            'priority' => 'الأولوية',
            'department' => 'القسم',
            'assigned_to' => 'المعين',
            'status' => 'الحالة',
            'resolution' => 'الحل',
            'customer_satisfaction' => 'تقييم رضا العميل',
        ];
    }
}
