<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = auth()->user();

        return [
            // Branch data
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:branches,email'],
            'phone' => ['required', 'string', 'max:20'],
            
            // Location
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            
            // Statistics
            'category' => ['required', 'string', 'max:255'],
            'compliance_score' => ['nullable', 'numeric', 'between:0,100'],
            'company_branch_limit' => [
                function ($attribute, $value, $fail) use($user) {
                    $companyId = request('company_id');
                    
                    // تحديد الشركة المطلوب التحقق منها
                    if ($user && $user->role === 'company_admin') {
                        // company_admin يضيف مستخدم لشركته فقط
                        $company = $user->company;
                    } elseif ($user && $user->role === 'super_admin' && $companyId) {
                        // super_admin يمكنه إضافة مستخدم لأي شركة
                        $company = Company::find($companyId);
                    } else {
                        // لا يوجد تحقق من الحد
                        return;
                    }
                    
                    // تحقق من وجود الشركة
                    if (!$company) {
                        $fail('الشركة غير موجودة');
                        return;
                    }
                    
                    $max_allowed_branches = $company->max_allowed_branches;
                    
                    // إذا كان max_allowed_branches = 0 يعني غير محدود
                    if ($max_allowed_branches > 0) {
                        $countUser = $company->branches()->count();
                        
                        if ($countUser >= $max_allowed_branches) {
                            $fail('هذه الشركة تجاوزت الحد الأقصى المسموح به من الفروع (' . $max_allowed_branches . ')');
                        }
                    }
                }
            ],
            // Branch Manager User data
            'user_id' => ['nullable', 'integer', 'exists:users,id'],


            'user_name' => [
                Rule::when($this->filled('user_id'),
                    ['sometimes', 'nullable', 'string', 'max:255'],  // إذا كان user_id موجوداً: اختياري
                    ['required', 'string', 'max:255']                // إذا كان user_id غير موجود: إجباري
                )
            ],
            
            'user_email' => [
                Rule::when($this->filled('user_id'),
                    [
                        'sometimes', 
                        'nullable', 
                        'email', 
                        Rule::unique('users', 'email')->ignore($this->user_id)
                    ],
                    ['required', 'email', 'unique:users,email']
                )
            ],
            
            'user_password' => [
                Rule::when($this->filled('user_id'),
                    ['sometimes', 'nullable', 'confirmed', Password::min(8)],  // اختياري عند التعديل
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
            'user_birth_date.before' => 'يجب أن يكون عمر مدير الفرع 18 عامًا على الأقل.',
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90.',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180.',
            'compliance_score.between' => 'درجة الامتثال يجب أن تكون بين 0 و 100.',
        ];
    }
}
