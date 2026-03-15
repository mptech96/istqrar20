<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCriteriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Criteria data
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['sometimes', 'string', 'max:100'],
            'weight' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            
            // Criteria ranges array
            'ranges' => ['sometimes', 'array', 'min:1'],
            'ranges.*.name' => ['required', 'string', 'max:100'],
            'ranges.*.min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'ranges.*.max_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'ranges.*.color' => ['nullable', 'string', 'max:10', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'ranges.*.description' => ['nullable', 'string'],
        ];
    }

     public function messages(): array
    {
        return [
            // Criteria data - بيانات المعيار
            'name.required' => 'حقل اسم المعيار مطلوب.',
            'name.string' => 'حقل اسم المعيار يجب أن يكون نصاً.',
            'name.max' => 'حقل اسم المعيار لا يتجاوز 255 حرفاً.',
            
            'description.string' => 'حقل الوصف يجب أن يكون نصاً.',
            
            'category.required' => 'حقل التصنيف مطلوب.',
            'category.string' => 'حقل التصنيف يجب أن يكون نصاً.',
            'category.max' => 'حقل التصنيف لا يتجاوز 100 حرفاً.',
            
            'weight.required' => 'حقل الوزن مطلوب.',
            'weight.numeric' => 'حقل الوزن يجب أن يكون رقماً.',
            'weight.min' => 'الوزن يجب أن يكون 0 على الأقل.',
            'weight.max' => 'الوزن لا يتجاوز 100.',
            
            'is_active.boolean' => 'حقل نشط يجب أن يكون قيمة منطقية (true/false).',
            
            'sort_order.integer' => 'حقل ترتيب الفرز يجب أن يكون رقماً صحيحاً.',
            'sort_order.min' => 'ترتيب الفرز يجب أن يكون 0 على الأقل.',
            
            // Criteria ranges array - مصفوفة نطاقات المعيار
            'ranges.required' => 'حقل النطاقات مطلوب.',
            'ranges.array' => 'حقل النطاقات يجب أن يكون مصفوفة.',
            'ranges.min' => 'يجب توفير نطاق واحد على الأقل.',
            
            'ranges.*.name.required' => 'حقل اسم النطاق مطلوب.',
            'ranges.*.name.string' => 'حقل اسم النطاق يجب أن يكون نصاً.',
            'ranges.*.name.max' => 'حقل اسم النطاق لا يتجاوز 100 حرفاً.',
            
            'ranges.*.min_score.required' => 'حقل الحد الأدنى للدرجة مطلوب.',
            'ranges.*.min_score.integer' => 'حقل الحد الأدنى للدرجة يجب أن يكون رقماً صحيحاً.',
            'ranges.*.min_score.min' => 'الحد الأدنى للدرجة يجب أن يكون 0 على الأقل.',
            'ranges.*.min_score.max' => 'الحد الأدنى للدرجة لا يتجاوز 100.',
            
            'ranges.*.max_score.required' => 'حقل الحد الأقصى للدرجة مطلوب.',
            'ranges.*.max_score.integer' => 'حقل الحد الأقصى للدرجة يجب أن يكون رقماً صحيحاً.',
            'ranges.*.max_score.min' => 'الحد الأقصى للدرجة يجب أن يكون 0 على الأقل.',
            'ranges.*.max_score.max' => 'الحد الأقصى للدرجة لا يتجاوز 100.',
            
            'ranges.*.color.string' => 'حقل اللون يجب أن يكون نصاً.',
            'ranges.*.color.max' => 'حقل اللون لا يتجاوز 10 أحرف.',
            'ranges.*.color.regex' => 'صيغة اللون غير صحيحة. يجب أن تكون بصيغة hex مثل #FFF أو #FFFFFF.',
            
            'ranges.*.description.string' => 'حقل وصف النطاق يجب أن يكون نصاً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المعيار',
            'description' => 'الوصف',
            'category' => 'التصنيف',
            'weight' => 'الوزن',
            'is_active' => 'نشط',
            'sort_order' => 'ترتيب الفرز',
            'ranges' => 'النطاقات',
            'ranges.*.name' => 'اسم النطاق',
            'ranges.*.min_score' => 'الحد الأدنى للدرجة',
            'ranges.*.max_score' => 'الحد الأقصى للدرجة',
            'ranges.*.color' => 'اللون',
            'ranges.*.description' => 'وصف النطاق',
        ];
    }
}
