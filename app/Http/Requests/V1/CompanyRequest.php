<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

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
            'subscription_start' => ['required', 'date'],
            'subscription_end' => ['required', 'date', 'after:subscription_start'],
            'subscription_status' => ['required', 'string', 'in:active,inactive,suspended'],
            'category' => ['required', 'string', 'max:100'],
            //'status' => ['boolean'],
            //'is_verified' => ['boolean'],
            
            // Company Manager User data
            'user_name' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'email', 'unique:users,email'],
            'user_password' => ['required', 'confirmed', Password::min(8)],
            'user_phone' => ['required', 'string', 'max:20'],
            'user_personal_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'user_birth_date' => ['nullable', 'date', 'before:-18 years'],
            'user_gender' => ['nullable', 'integer', 'in:1,2,3'],
            //'user_status' => ['boolean'],
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
