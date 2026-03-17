<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['sometimes', 'string', Rule::in(['package_request', 'contact_message'])],
            'package_name' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50', function ($attribute, $value, $fail) {if (!preg_match('/^[0-9+\-\s]+$/', $value)) {$fail('يرجى إدخال رقم هاتف صحيح');}}],
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
            'request_type.in' => 'نوع الطلب يجب أن يكون package_request أو contact_message.',
            'status.in' => 'الحالة يجب أن تكون new, in_review أو closed.',
        ];
    }
}