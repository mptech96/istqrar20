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
            'phone' => ['required', 'string', 'max:20'],
            
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
            'birth_date.before' => 'يجب أن يكون عمر المستخدم 18 عامًا على الأقل.',
            'national_number.unique' => 'الرقم الوطني مسجل مسبقًا.',
            'role.in' => 'الدور يجب أن يكون أحد: super_admin, auditor, company_admin, branch_manager',
            'gender.in' => 'الجنس يجب أن يكون أحد: male, female, other',
            
            // Custom messages for file uploads
            'municipality_certificate.mimes' => 'شهادة البلدية يجب أن تكون من نوع: pdf, jpg, jpeg, png',
            'municipality_certificate.max' => 'حجم شهادة البلدية لا يتجاوز 5 ميجابايت',
            'driving_license.mimes' => 'رخصة القيادة يجب أن تكون من نوع: pdf, jpg, jpeg, png',
            'driving_license.max' => 'حجم رخصة القيادة لا يتجاوز 5 ميجابايت',
            'personal_photo.image' => 'الصورة الشخصية يجب أن تكون صورة',
            'personal_photo.mimes' => 'الصورة الشخصية يجب أن تكون من نوع: jpg, jpeg, png',
            'personal_photo.max' => 'حجم الصورة الشخصية لا يتجاوز 2 ميجابايت',
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
        }

        // Add validation marker for user limit check
        if ($this->shouldValidateUserLimit(auth()->user())) {
            $this->merge([
                'user_limit_validation' => true,
            ]);
        }
    }
}