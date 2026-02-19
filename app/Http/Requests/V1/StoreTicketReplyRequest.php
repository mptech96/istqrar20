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
            'message.required' => 'نص الرد مطلوب',
            'attachments.*.max' => 'الملف كبير جداً (الحد الأقصى 10 ميجابايت)',
        ];
    }
}
