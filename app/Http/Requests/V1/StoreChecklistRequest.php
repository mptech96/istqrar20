<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Checklist data
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:checklists,code'],
            'description' => ['nullable', 'string'],
            'version' => ['nullable', 'string', 'max:20'],
            
            // Target information
            'company_id' => ['nullable', 'exists:companies,id'],
            'industry_type' => ['nullable', 'string', 'max:100'],
            
            // Settings
            'total_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'passing_score' => ['required', 'integer', 'min:0', 'lte:total_score'],
            'time_estimate' => ['nullable', 'integer', 'min:1'],
            
            // Status
            'is_active' => ['nullable', 'boolean'],
            'is_template' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            // Checklist data - بيانات قائمة التحقق
            'name.required' => 'حقل اسم قائمة التحقق مطلوب.',
            'name.string' => 'حقل اسم قائمة التحقق يجب أن يكون نصاً.',
            'name.max' => 'حقل اسم قائمة التحقق لا يتجاوز 255 حرفاً.',
            
            'code.string' => 'حقل الكود يجب أن يكون نصاً.',
            'code.max' => 'حقل الكود لا يتجاوز 50 حرفاً.',
            'code.unique' => 'الكود مستخدم بالفعل لقائمة تحقق أخرى.',
            
            'description.string' => 'حقل الوصف يجب أن يكون نصاً.',
            
            'version.string' => 'حقل الإصدار يجب أن يكون نصاً.',
            'version.max' => 'حقل الإصدار لا يتجاوز 20 حرفاً.',
            
            // Target information - معلومات الهدف
            'company_id.exists' => 'الشركة المحددة غير موجودة.',
            
            'industry_type.string' => 'حقل نوع الصناعة يجب أن يكون نصاً.',
            'industry_type.max' => 'حقل نوع الصناعة لا يتجاوز 100 حرفاً.',
            
            // Settings - الإعدادات
            'total_score.required' => 'حقل الدرجة الإجمالية مطلوب.',
            'total_score.integer' => 'حقل الدرجة الإجمالية يجب أن يكون رقماً صحيحاً.',
            'total_score.min' => 'الدرجة الإجمالية يجب أن تكون 1 على الأقل.',
            'total_score.max' => 'الدرجة الإجمالية لا تتجاوز 1000.',
            
            'passing_score.required' => 'حقل درجة النجاح مطلوب.',
            'passing_score.integer' => 'حقل درجة النجاح يجب أن يكون رقماً صحيحاً.',
            'passing_score.min' => 'درجة النجاح يجب أن تكون 0 على الأقل.',
            'passing_score.lte' => 'درجة النجاح يجب أن تكون أقل من أو تساوي الدرجة الإجمالية.',
            
            'time_estimate.integer' => 'حقل الوقت التقديري يجب أن يكون رقماً صحيحاً.',
            'time_estimate.min' => 'الوقت التقديري يجب أن يكون دقيقة واحدة على الأقل.',
            
            // Status - الحالة
            'is_active.boolean' => 'حقل نشط يجب أن يكون قيمة منطقية (true/false).',
            'is_template.boolean' => 'حقل قالب يجب أن يكون قيمة منطقية (true/false).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم قائمة التحقق',
            'code' => 'الكود',
            'description' => 'الوصف',
            'version' => 'الإصدار',
            'company_id' => 'الشركة',
            'industry_type' => 'نوع الصناعة',
            'total_score' => 'الدرجة الإجمالية',
            'passing_score' => 'درجة النجاح',
            'time_estimate' => 'الوقت التقديري (بالدقائق)',
            'is_active' => 'نشط',
            'is_template' => 'قالب',
        ];
    }
}
