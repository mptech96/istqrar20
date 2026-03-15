<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // يمكن تخصيص الصلاحيات لاحقاً
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', 'string', Rule::in(['package_request', 'contact_message'])],
            'package_name' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['new', 'in_review', 'closed'])],
            'admin_notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'نوع الطلب مطلوب.',
            'request_type.in' => 'نوع الطلب يجب أن يكون package_request أو contact_message.',
            'customer_name.required' => 'اسم العميل مطلوب.',
            'phone.required' => 'رقم الجوال مطلوب.',
        ];
    }
}