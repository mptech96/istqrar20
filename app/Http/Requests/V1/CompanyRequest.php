<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Company data
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:companies,email'],
            'phone' => ['required', 'string', 'max:20'],
            'commercial_registration' => ['required', 'string', 'max:100', 'unique:companies,commercial_registration'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string'],
            'contact_person' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'max_allowed_users' => ['nullable', 'integer', 'min:2'],
            'max_allowed_branches' => ['nullable', 'integer', 'min:1'],

            'subscription_start' => ['required', 'date', 'after_or_equal:today'],
            'subscription_end' => ['required', 'date', 'after:subscription_start'],
            'subscription_status' => ['required', 'string', 'in:active,inactive,suspended'],
            'category' => ['required', 'string', 'max:100'],
            
            // Company Manager User data - Conditional validation based on user_id
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'user_name' => [
                Rule::when($this->filled('user_id'), 
                    ['sometimes', 'nullable', 'string', 'max:255'],  // إذا كان user_id موجوداً: اختياري
                    ['required', 'string', 'max:255']                // إذا كان user_id غير موجود: إجباري
                )
            ],
            'user_email' => [
                Rule::when($this->filled('user_id'),
                    ['sometimes', 'nullable', 'email', Rule::unique('users', 'email')->ignore($this->user_id)],
                    ['required', 'email', 'unique:users,email']
                )
            ],
            'user_password' => [
                Rule::when($this->filled('user_id'),
                    ['sometimes', 'nullable', 'confirmed', Password::min(8)],  // اختياري مع إمكانية التعديل
                    ['required', 'confirmed', Password::min(8)]                 // إجباري عند الإنشاء
                )
            ],
            'user_phone' => [
                Rule::when($this->filled('user_id'),
                    ['sometimes', 'nullable', 'string', 'max:20'],
                    ['required', 'string', 'max:20']
                )
            ],
            'user_personal_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'user_birth_date' => ['nullable', 'date', 'before:-18 years'],
            'user_gender' => ['nullable', 'integer', 'in:1,2,3'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_birth_date.before' => 'يجب أن يكون عمر المدير 18 عامًا على الأقل.',
            'subscription_end.after' => 'تاريخ انتهاء الاشتراك يجب أن يكون بعد تاريخ البداية.',
        ];
    }
}
