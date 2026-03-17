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
            'phone' => ['sometimes', 'string', 'max:20', function ($attribute, $value, $fail) {if (!preg_match('/^[0-9+\-\s]+$/', $value)) {$fail('يرجى إدخال رقم هاتف صحيح');}}],
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
            // Basic information - المعلومات الأساسية
            'name.required' => 'حقل الاسم مطلوب.',
            'name.string' => 'حقل الاسم يجب أن يكون نصاً.',
            'name.max' => 'حقل الاسم لا يتجاوز 255 حرفاً.',
            
            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            
            'password.required' => 'حقل كلمة المرور مطلوب.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            
            'phone.required' => 'حقل رقم الهاتف مطلوب.',
            'phone.string' => 'حقل رقم الهاتف يجب أن يكون نصاً.',
            'phone.max' => 'حقل رقم الهاتف لا يتجاوز 20 رقماً.',
            
            // Role and assignment - الدور والتعيين
            'role.required' => 'حقل الدور مطلوب.',
            'role.string' => 'حقل الدور يجب أن يكون نصاً.',
            'role.in' => 'الدور يجب أن يكون أحد: super_admin, auditor, company_admin, branch_manager',
            
            // Company and branch - الشركة والفرع
            'department.string' => 'حقل القسم يجب أن يكون نصاً.',
            'department.max' => 'حقل القسم لا يتجاوز 100 حرفاً.',
            
            'position.string' => 'حقل المسمى الوظيفي يجب أن يكون نصاً.',
            'position.max' => 'حقل المسمى الوظيفي لا يتجاوز 100 حرفاً.',
            
            'company_id.exists' => 'الشركة المحددة غير موجودة.',
            'branch_id.exists' => 'الفرع المحدد غير موجود.',
            
            // Personal information - المعلومات الشخصية
            'birth_date.date' => 'حقل تاريخ الميلاد يجب أن يكون تاريخاً صحيحاً.',
            'birth_date.before' => 'يجب أن يكون عمر المستخدم 18 عامًا على الأقل.',
            
            'gender.string' => 'حقل الجنس يجب أن يكون نصاً.',
            'gender.in' => 'الجنس يجب أن يكون أحد: male, female, other',
            
            'national_number.string' => 'حقل الرقم الوطني يجب أن يكون نصاً.',
            'national_number.max' => 'حقل الرقم الوطني لا يتجاوز 50 حرفاً.',
            'national_number.unique' => 'الرقم الوطني مسجل مسبقًا.',
            
            'must_change_password.boolean' => 'حقل تغيير كلمة المرور يجب أن يكون قيمة منطقية (true/false).',
            
            // Auditor-specific validation - التحقق الخاص بالمراجع
            'municipality_certificate.file' => 'شهادة البلدية يجب أن تكون ملفاً.',
            'municipality_certificate.mimes' => 'شهادة البلدية يجب أن تكون من نوع: pdf, jpg, jpeg, png',
            'municipality_certificate.max' => 'حجم شهادة البلدية لا يتجاوز 5 ميجابايت',
            
            'driving_license.file' => 'رخصة القيادة يجب أن تكون ملفاً.',
            'driving_license.mimes' => 'رخصة القيادة يجب أن تكون من نوع: pdf, jpg, jpeg, png',
            'driving_license.max' => 'حجم رخصة القيادة لا يتجاوز 5 ميجابايت',
            
            'personal_photo.image' => 'الصورة الشخصية يجب أن تكون صورة',
            'personal_photo.mimes' => 'الصورة الشخصية يجب أن تكون من نوع: jpg, jpeg, png',
            'personal_photo.max' => 'حجم الصورة الشخصية لا يتجاوز 2 ميجابايت',
            
            // Document expiry dates - تواريخ انتهاء المستندات
            'certificate_expiry.date' => 'تاريخ انتهاء الشهادة يجب أن يكون تاريخاً صحيحاً.',
            'license_expiry.date' => 'تاريخ انتهاء الرخصة يجب أن يكون تاريخاً صحيحاً.',
            'id_expiry.date' => 'تاريخ انتهاء الهوية يجب أن يكون تاريخاً صحيحاً.',
            
            // Document validity - صلاحية المستندات
            'is_certificate_valid.boolean' => 'حقل صلاحية الشهادة يجب أن يكون قيمة منطقية (true/false).',
            'is_license_valid.boolean' => 'حقل صلاحية الرخصة يجب أن يكون قيمة منطقية (true/false).',
            'is_id_valid.boolean' => 'حقل صلاحية الهوية يجب أن يكون قيمة منطقية (true/false).',
            
            // Custom validation message for user limit - رسالة مخصصة للتحقق من حد المستخدمين
            'user_limit_validation.required' => 'يجب التحقق من حد المستخدمين المسموح به.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'phone' => 'الهاتف',
            'role' => 'الدور',
            'company_id' => 'الشركة',
            'branch_id' => 'الفرع',
            'department' => 'القسم',
            'position' => 'المسمى الوظيفي',
            'birth_date' => 'تاريخ الميلاد',
            'gender' => 'الجنس',
            'national_number' => 'الرقم الوطني',
            'municipality_certificate' => 'شهادة البلدية',
            'driving_license' => 'رخصة القيادة',
            'personal_photo' => 'الصورة الشخصية',
            'certificate_expiry' => 'تاريخ انتهاء الشهادة',
            'license_expiry' => 'تاريخ انتهاء الرخصة',
            'id_expiry' => 'تاريخ انتهاء الهوية',
        ];
    }

}
