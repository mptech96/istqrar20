<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Models\V1\Company;
use App\Models\V1\User;

class StoreUserRequest extends FormRequest
{
    private ?Company $targetCompany = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = auth()->user();

        $rules = [
            // Basic information
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {if (!preg_match('/^[0-9+\-\s]+$/', $value)) {$fail('يرجى إدخال رقم هاتف صحيح');}}],
            
            // Role and assignment
            'role' => ['required', 'string', $this->getRoleValidationRule($user)],
            
            // Company and branch
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            
            // Personal information
            'birth_date' => ['nullable', 'date', 'before:-18 years'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            
            'must_change_password' => ['nullable', 'boolean'],
        ];
        // Add user limit validation for company users
        $rules = array_merge($rules, $this->getUserLimitValidationRules($user));

        // Add auditor-specific validation
        if ($this->input('role') === 'auditor') {
            $rules = array_merge($rules, $this->getAuditorValidationRules());
        } else {
            $rules['national_number'] = ['nullable', 'string', 'max:50', 'unique:users,national_number'];
            $rules['company_id'] = ['nullable', 'exists:companies,id'];
            $rules['branch_id'] = ['nullable', 'exists:branches,id'];
        }

        return $rules;
    }

    /**
     * Get role validation rule based on user permissions
     */
    private function getRoleValidationRule($user): string
    {
        if ($user && $user->role === 'company_admin') {
            return 'in:company_admin,branch_manager';
        }
        return 'in:super_admin,auditor,company_admin,branch_manager';
    }

    /**
     * Get user limit validation rules
     */
    private function getUserLimitValidationRules($user): array
    {
        $rules = [];

        // Only validate if we need to check user limits
        if (!$this->shouldValidateUserLimit($user)) {
            return $rules;
        }

        $rules['user_limit_validation'] = [
            'required',
            function ($attribute, $value, $fail) use ($user) {
                $company = $this->getTargetCompany($user);
                
                if (!$company) {
                    $fail('الشركة غير موجودة أو غير محددة.');
                    return;
                }

                $this->validateUserLimit($company, $fail);
            },
        ];

        return $rules;
    }

    /**
     * Determine if we should validate user limit
     */
    private function shouldValidateUserLimit($user): bool
    {
        if (!$user) {
            return false;
        }

        // Company admin always needs validation
        if ($user->role === 'company_admin') {
            return true;
        }

        // Super admin only needs validation if company_id is provided
        if ($user->role === 'super_admin' && $this->input('company_id')) {
            return true;
        }

        return false;
    }

    /**
     * Get the target company for validation
     */
    private function getTargetCompany($user): ?Company
    {
        if ($this->targetCompany !== null) {
            return $this->targetCompany;
        }

        if ($user->role === 'company_admin') {
            $this->targetCompany = $user->company;
        } elseif ($user->role === 'super_admin' && $this->input('company_id')) {
            $this->targetCompany = Company::find($this->input('company_id'));
        }

        return $this->targetCompany;
    }

    /**
     * Validate user limit for a company
     */
    private function validateUserLimit(Company $company, $fail): void
    {
        $maxAllowedUsers = $company->max_allowed_users;

        // If max_allowed_users = 0 means unlimited
        if ($maxAllowedUsers <= 0) {
            return;
        }

        $currentUsersCount = $company->users()->count();

        if ($currentUsersCount >= $maxAllowedUsers) {
            $fail($this->getUserLimitExceededMessage($maxAllowedUsers, $company->name));
        }
    }

    /**
     * Get user limit exceeded message
     */
    private function getUserLimitExceededMessage(int $maxAllowedUsers, string $companyName): string
    {
        return sprintf(
            'لا يمكن إضافة مستخدم جديد. الشركة "%s" تجاوزت الحد الأقصى المسموح به من المستخدمين (%d).',
            $companyName,
            $maxAllowedUsers
        );
    }

    /**
     * Get auditor-specific validation rules
     */
    private function getAuditorValidationRules(): array
    {
        return [
            'national_number' => ['required', 'string', 'max:50', 'unique:users,national_number'],
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
        ];
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

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (in_array($this->input('role'), ['auditor', 'super_admin'])) {
            $this->merge([
                'company_id' => null,
                'branch_id' => null,
            ]);
        }
        
        if (!$this->has('must_change_password')) {
            $this->merge([
                'must_change_password' => true, // Force password change on first login
            ]);
        } else {
            $this->merge([
                'must_change_password' => filter_var($this->input('must_change_password'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Add validation marker for user limit check
        if ($this->shouldValidateUserLimit(auth()->user())) {
            $this->merge([
                'user_limit_validation' => true,
            ]);
        }
    }
}