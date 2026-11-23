<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $company = $this->route('company') ? $this->route('company') : null;

        return [
            // Company data
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:companies,email,' . $company->id],
            'phone' => ['sometimes', 'string', 'max:20', function ($attribute, $value, $fail) {if (!preg_match('/^[0-9+\-\s]+$/', $value)) {$fail('يرجى إدخال رقم هاتف صحيح');}}],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'commercial_registration' => ['sometimes', 'string', 'max:50', 'unique:companies,commercial_registration,' . $company->id],
            'city' => ['sometimes', 'string', 'max:100'],
            'address' => ['sometimes', 'string'],
            'contact_person' => ['sometimes', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'subscription_start' => ['sometimes', 'date'],
            'subscription_end' => ['sometimes', 'date', 'after:subscription_start'],
            'subscription_status' => ['sometimes', 'string', 'in:active,expired,suspended,pending'],
            'max_allowed_users' => ['sometimes', 'integer', 'min:0'],
            'max_allowed_branches' => ['sometimes', 'integer', 'min:0'],
            //'status' => ['sometimes'],
            'is_verified' => ['sometimes', 'boolean'],
            'ai_support' => ['sometimes', 'boolean'],
            'auto_renew' => ['sometimes', 'boolean'],
            'show_on_website' => ['sometimes', 'boolean'],
            'user_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['company_admin']))],
        ];
    }

    public function messages(): array
    {
        return [
            // رسائل حقول الشركة
            'name.required' => 'حقل اسم الشركة مطلوب.',
            'name.string' => 'حقل اسم الشركة يجب أن يكون نصاً.',
            'name.max' => 'حقل اسم الشركة لا يتجاوز 255 حرفاً.',
            
            'email.required' => 'حقل البريد الإلكتروني للشركة مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني للشركة غير صحيحة.',
            'email.unique' => 'البريد الإلكتروني للشركة مستخدم بالفعل.',
            
            'phone.required' => 'حقل رقم الهاتف مطلوب.',
            'phone.string' => 'حقل رقم الهاتف يجب أن يكون نصاً.',
            'phone.max' => 'حقل رقم الهاتف لا يتجاوز 20 رقماً.',
                        
            'latitude.numeric' => 'حقل خط العرض يجب أن يكون رقماً.',
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90.',
            
            'longitude.numeric' => 'حقل خط الطول يجب أن يكون رقماً.',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180.',
            
            'commercial_registration.required' => 'حقل السجل التجاري مطلوب.',
            'commercial_registration.string' => 'حقل السجل التجاري يجب أن يكون نصاً.',
            'commercial_registration.max' => 'حقل السجل التجاري لا يتجاوز 100 حرفاً.',
            'commercial_registration.unique' => 'السجل التجاري مستخدم بالفعل.',
            
            'city.required' => 'حقل المدينة مطلوب.',
            'city.string' => 'حقل المدينة يجب أن يكون نصاً.',
            'city.max' => 'حقل المدينة لا يتجاوز 100 حرفاً.',
            
            'address.required' => 'حقل العنوان مطلوب.',
            'address.string' => 'حقل العنوان يجب أن يكون نصاً.',
            
            'contact_person.required' => 'حقل شخص الاتصال مطلوب.',
            'contact_person.string' => 'حقل شخص الاتصال يجب أن يكون نصاً.',
            'contact_person.max' => 'حقل شخص الاتصال لا يتجاوز 255 حرفاً.',
            
            'logo.image' => 'يجب أن يكون الشعار صورة.',
            'logo.mimes' => 'صيغة الشعار يجب أن تكون jpg, jpeg, png.',
            'logo.max' => 'حجم الشعار لا يتجاوز 2 ميجابايت.',
            
            'max_allowed_users.integer' => 'الحد الأقصى للمستخدمين يجب أن يكون رقماً صحيحاً.',
            'max_allowed_users.min' => 'الحد الأقصى للمستخدمين يجب أن يكون 2 على الأقل.',
            
            'max_allowed_branches.integer' => 'الحد الأقصى للفروع يجب أن يكون رقماً صحيحاً.',
            'max_allowed_branches.min' => 'الحد الأقصى للفروع يجب أن يكون 1 على الأقل.',
            
            'subscription_start.required' => 'حقل تاريخ بداية الاشتراك مطلوب.',
            'subscription_start.date' => 'حقل تاريخ بداية الاشتراك يجب أن يكون تاريخاً صحيحاً.',
            'subscription_start.after_or_equal' => 'تاريخ بداية الاشتراك يجب أن يكون اليوم أو تاريخ لاحق.',
            
            'subscription_end.required' => 'حقل تاريخ انتهاء الاشتراك مطلوب.',
            'subscription_end.date' => 'حقل تاريخ انتهاء الاشتراك يجب أن يكون تاريخاً صحيحاً.',
            'subscription_end.after' => 'تاريخ انتهاء الاشتراك يجب أن يكون بعد تاريخ البداية.',
            
            'subscription_status.required' => 'حقل حالة الاشتراك مطلوب.',
            'subscription_status.string' => 'حقل حالة الاشتراك يجب أن يكون نصاً.',
            'subscription_status.in' => 'حالة الاشتراك يجب أن تكون active, inactive, أو suspended.',
            
            'category.required' => 'حقل التصنيف مطلوب.',
            'category.string' => 'حقل التصنيف يجب أن يكون نصاً.',
            'category.max' => 'حقل التصنيف لا يتجاوز 100 حرفاً.',
            
            'ai_support.boolean' => 'حقل دعم الذكاء الاصطناعي يجب أن يكون قيمة منطقية (true/false).',
            'is_verified.boolean' => 'الحقل يجب أن يكون قيمة منطقية (true/false).',

            // رسائل حقول مدير الشركة
            'user_id.integer' => 'معرف المستخدم يجب أن يكون رقماً صحيحاً.',
            'user_id.exists' => 'المستخدم المحدد غير موجود.',
            
            'user_name.required' => 'حقل اسم المدير مطلوب.',
            'user_name.string' => 'حقل اسم المدير يجب أن يكون نصاً.',
            'user_name.max' => 'حقل اسم المدير لا يتجاوز 255 حرفاً.',
            
            'user_email.required' => 'حقل البريد الإلكتروني للمدير مطلوب.',
            'user_email.email' => 'صيغة البريد الإلكتروني للمدير غير صحيحة.',
            'user_email.unique' => 'البريد الإلكتروني للمدير مستخدم بالفعل.',
            
            'user_password.required' => 'حقل كلمة المرور للمدير مطلوب.',
            'user_password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'user_password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            
            'user_phone.required' => 'حقل رقم هاتف المدير مطلوب.',
            'user_phone.string' => 'حقل رقم هاتف المدير يجب أن يكون نصاً.',
            'user_phone.max' => 'حقل رقم هاتف المدير لا يتجاوز 20 رقماً.',
            
            'user_personal_photo.image' => 'يجب أن تكون الصورة الشخصية صورة.',
            'user_personal_photo.mimes' => 'صيغة الصورة الشخصية يجب أن تكون jpg, jpeg, png.',
            'user_personal_photo.max' => 'حجم الصورة الشخصية لا يتجاوز 2 ميجابايت.',
            
            'user_birth_date.date' => 'حقل تاريخ الميلاد يجب أن يكون تاريخاً صحيحاً.',
            'user_birth_date.before' => 'يجب أن يكون عمر المدير 18 عامًا على الأقل.',
            
            'user_gender.integer' => 'حقل الجنس يجب أن يكون رقماً صحيحاً.',
            'user_gender.in' => 'قيم الجنس المقبولة هي 1, 2, 3.',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['auto_renew', 'show_on_website'] as $flag) {
            if ($this->filled($flag)) {
                $this->merge([$flag => filter_var($this->input($flag), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
        if ($this->filled('ai_support')) {
            $this->merge(['ai_support' => filter_var($this->input('ai_support'), FILTER_VALIDATE_BOOLEAN),]);
        }
        if ($this->filled('is_verified')) {
            $this->merge(['is_verified' => filter_var($this->input('is_verified'), FILTER_VALIDATE_BOOLEAN),]);
        }
    }
}
