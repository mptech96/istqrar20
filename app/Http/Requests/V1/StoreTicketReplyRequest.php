<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'], // 10MB max per file
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'حقل الرسالة مطلوب.',
            'message.string' => 'حقل الرسالة يجب أن يكون نصاً.',
            
            'is_internal.boolean' => 'حقل داخلي يجب أن يكون قيمة منطقية (true/false).',
            
            'attachments.array' => 'حقل المرفقات يجب أن يكون مصفوفة.',
            
            'attachments.*.file' => 'كل مرفق يجب أن يكون ملفاً.',
            'attachments.*.max' => 'حجم الملف لا يتجاوز 10 ميجابايت.',
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'الرسالة',
            'is_internal' => 'داخلي',
            'attachments' => 'المرفقات',
            'attachments.*' => 'الملف المرفق',
        ];
    }
}
