<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch') ? $this->route('branch')->id : null;

        return [
            // Branch data
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:branches,email,' . $branchId],
            'phone' => ['sometimes', 'string', 'max:20', function ($attribute, $value, $fail) {if (!preg_match('/^[0-9+\-\s]+$/', $value)) {$fail('يرجى إدخال رقم هاتف صحيح');}}],
            
            // Location
            'city' => ['sometimes', 'string', 'max:100'],
            'address' => ['sometimes', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            
            // Statistics
            'category' => ['sometimes', 'string', 'max:255'],
            'compliance_score' => ['nullable', 'numeric', 'between:0,100'],
            'total_visits' => ['nullable', 'integer', 'min:0'],
            'last_visit_date' => ['nullable', 'date'],
            'next_scheduled_visit' => ['nullable', 'date'],
            
            
            // Manager update
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_email' => ['nullable', 'email'],
            'manager_phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            // رسائل حقول الفرع
            'company_id.required' => 'حقل معرف الشركة مطلوب.',
            'company_id.exists' => 'الشركة المحددة غير موجودة.',
            
            'name.required' => 'حقل اسم الفرع مطلوب.',
            'name.string' => 'حقل اسم الفرع يجب أن يكون نصاً.',
            'name.max' => 'حقل اسم الفرع لا يتجاوز 255 حرفاً.',
            
            'email.required' => 'حقل البريد الإلكتروني للفرع مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني للفرع غير صحيحة.',
            'email.unique' => 'البريد الإلكتروني للفرع مستخدم بالفعل.',
            
            'phone.required' => 'حقل رقم الهاتف للفرع مطلوب.',
            'phone.string' => 'حقل رقم الهاتف للفرع يجب أن يكون نصاً.',
            'phone.max' => 'حقل رقم الهاتف للفرع لا يتجاوز 20 رقماً.',
            
            // رسائل الموقع
            'city.required' => 'حقل المدينة مطلوب.',
            'city.string' => 'حقل المدينة يجب أن يكون نصاً.',
            'city.max' => 'حقل المدينة لا يتجاوز 100 حرفاً.',
            
            'address.required' => 'حقل العنوان مطلوب.',
            'address.string' => 'حقل العنوان يجب أن يكون نصاً.',
            
            'latitude.numeric' => 'حقل خط العرض يجب أن يكون رقماً.',
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90.',
            
            'longitude.numeric' => 'حقل خط الطول يجب أن يكون رقماً.',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180.',
            
            // رسائل الإحصائيات
            'category.required' => 'حقل التصنيف مطلوب.',
            'category.string' => 'حقل التصنيف يجب أن يكون نصاً.',
            'category.max' => 'حقل التصنيف لا يتجاوز 255 حرفاً.',
            
            'compliance_score.numeric' => 'نسبة الامتثال يجب أن تكون رقماً.',
            'compliance_score.between' => 'نسبة الامتثال يجب أن تكون بين 0 و 100.',
            
            // رسائل مدير الفرع
            'user_id.integer' => 'معرف المستخدم يجب أن يكون رقماً صحيحاً.',
            'user_id.exists' => 'المستخدم المحدد غير موجود.',
            
            'user_name.required' => 'حقل اسم مدير الفرع مطلوب.',
            'user_name.string' => 'حقل اسم مدير الفرع يجب أن يكون نصاً.',
            'user_name.max' => 'حقل اسم مدير الفرع لا يتجاوز 255 حرفاً.',
            
            'user_email.required' => 'حقل البريد الإلكتروني لمدير الفرع مطلوب.',
            'user_email.email' => 'صيغة البريد الإلكتروني لمدير الفرع غير صحيحة.',
            'user_email.unique' => 'البريد الإلكتروني لمدير الفرع مستخدم بالفعل.',
            
            'user_password.required' => 'حقل كلمة المرور لمدير الفرع مطلوب.',
            'user_password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'user_password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            
            'user_phone.required' => 'حقل رقم هاتف مدير الفرع مطلوب.',
            'user_phone.string' => 'حقل رقم هاتف مدير الفرع يجب أن يكون نصاً.',
            'user_phone.max' => 'حقل رقم هاتف مدير الفرع لا يتجاوز 20 رقماً.',
            
            'user_personal_photo.image' => 'يجب أن تكون الصورة الشخصية صورة.',
            'user_personal_photo.mimes' => 'صيغة الصورة الشخصية يجب أن تكون jpg, jpeg, png.',
            'user_personal_photo.max' => 'حجم الصورة الشخصية لا يتجاوز 2 ميجابايت.',
            
            'user_birth_date.date' => 'حقل تاريخ الميلاد يجب أن يكون تاريخاً صحيحاً.',
            'user_birth_date.before' => 'يجب أن يكون عمر مدير الفرع 18 عامًا على الأقل.',
            
            'user_gender.integer' => 'حقل الجنس يجب أن يكون رقماً صحيحاً.',
            'user_gender.in' => 'قيم الجنس المقبولة هي 1, 2, 3.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ( auth()->user()->role !== 'super_admin' ) {
            $this->merge([
                'compliance_score' => 0.00,
                'total_visits' => 0,
                'last_visit_date' => null,
                'next_scheduled_visit' => null,
            ]);
        }
    }
}
