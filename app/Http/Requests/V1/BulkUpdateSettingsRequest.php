<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role == 'super_admin';
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.group' => ['required', 'string', 'max:100'],
            'settings.*.key' => ['required', 'string', 'max:100'],
            'settings.*.value' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'يجب توفير قائمة الإعدادات',
            'settings.*.group.required' => 'مجموعة الإعداد مطلوبة',
            'settings.*.key.required' => 'مفتاح الإعداد مطلوب',
        ];
    }
}
