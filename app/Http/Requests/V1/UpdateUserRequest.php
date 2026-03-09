<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : null;

        $rules = [
            // Basic information
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $userId],
            'phone' => ['sometimes', 'string', 'max:20'],
            'password' => ['sometimes', 'confirmed', Password::min(8)],
            
            // Role and assignment
            'role' => ['sometimes', 'string', 'in:super_admin,auditor,company_admin,branch_manager'],
            
            // Company and branch
            'company_id' => ['nullable', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            
            // Personal information
            'birth_date' => ['nullable', 'date', 'before:-18 years'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            
            // Status
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'must_change_password' => ['nullable', 'boolean'],
        ];

        // Add auditor-specific validation if role is being changed to auditor
        if ($this->input('role') === 'auditor' || 
            ($this->route('user') && $this->route('user')->role === 'auditor')) {
            
            $rules = array_merge($rules, [
                'national_number' => ['sometimes', 'string', 'max:50', 'unique:users,national_number,' . $userId],
                'municipality_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'driving_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'personal_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
                
                // Document expiry dates
                'certificate_expiry' => ['nullable', 'date'],
                'license_expiry' => ['nullable', 'date'],
                'id_expiry' => ['nullable', 'date'],
                
                // Document validity
                'is_certificate_valid' => ['nullable', 'boolean'],
                'is_license_valid' => ['nullable', 'boolean'],
                'is_id_valid' => ['nullable', 'boolean'],
            ]);
        } else {
            $rules['national_number'] = ['nullable', 'string', 'max:50', 'unique:users,national_number,' . $userId];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'birth_date.before' => 'يجب أن يكون عمر المستخدم 18 عامًا على الأقل.',
            'national_number.unique' => 'الرقم الوطني مسجل مسبقًا.',
            'role.in' => 'الدور يجب أن يكون أحد: super_admin, auditor, company_admin, branch_manager',
            'gender.in' => 'الجنس يجب أن يكون أحد: male, female, other',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'phone' => 'الهاتف',
            'role' => 'الدور',
            'national_number' => 'الرقم الوطني',
        ];
    }
}
