<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            // Question data
            //'checklist_id' => ['required', 'exists:checklists,id'],
            'criteria_id' => ['required', 'exists:criterias,id'],
            
            'question_text' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'help_text' => ['nullable', 'string'],
            
            // Question type
            'type' => ['required', 'string', 'in:yes_no,scale_1_5,scale_1_10,multiple_choice,text,file,date,number'],
            
            // Weight and scoring
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
            'max_score' => ['required', 'integer', 'min:1', 'max:100'],
            'passing_score' => ['nullable', 'integer', 'min:0', 'lte:max_score'],
            
            // Settings
            'is_required' => ['nullable', 'boolean'],
            'is_critical' => ['nullable', 'boolean'],
            'has_attachment' => ['nullable', 'boolean'],
            'attachment_instructions' => ['nullable', 'string'],
            
            // Order and status
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];

        // Add validation for options if type is multiple_choice
        if ($this->input('type') === 'multiple_choice') {
            $rules['options'] = ['required', 'array', 'min:2'];
            $rules['options.*.value'] = ['required', 'string', 'max:255'];
            $rules['options.*.score'] = ['required', 'integer', 'min:0', 'max:100'];
            $rules['correct_answer'] = ['nullable', 'string', 'max:255'];
        } elseif ($this->input('type') === 'scale_1_5' || $this->input('type') === 'scale_1_10') {
            $rules['options'] = ['nullable'];
        } else {
            $rules['options'] = ['nullable'];
            $rules['correct_answer'] = ['nullable'];
        }

        return $rules;
    }


    /**
     * توافق مع الواجهة: النوع "rating" (تقييم 1-5) يُخزَّن كنوع scale_1_5
     */
    protected function prepareForValidation()
    {
        if ($this->input('type') === 'rating') {
            $this->merge(['type' => 'scale_1_5']);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate that options array has unique values
            if ($this->has('options') && $this->input('type') === 'multiple_choice') {
                $options = $this->input('options', []);
                $values = array_column($options, 'value');
                
                if (count($values) !== count(array_unique($values))) {
                    $validator->errors()->add(
                        'options',
                        'قيم الخيارات يجب أن تكون فريدة.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            // Question data - بيانات السؤال
            'checklist_id.required' => 'حقل معرف قائمة التحقق مطلوب.',
            'checklist_id.exists' => 'قائمة التحقق المحددة غير موجودة.',
            
            'criteria_id.required' => 'حقل معرف المعيار مطلوب.',
            'criteria_id.exists' => 'المعيار المحدد غير موجود.',
            
            'question_text.required' => 'حقل نص السؤال مطلوب.',
            'question_text.string' => 'حقل نص السؤال يجب أن يكون نصاً.',
            
            'description.string' => 'حقل الوصف يجب أن يكون نصاً.',
            
            'help_text.string' => 'حقل نص المساعدة يجب أن يكون نصاً.',
            
            // Question type - نوع السؤال
            'type.required' => 'حقل نوع السؤال مطلوب.',
            'type.string' => 'حقل نوع السؤال يجب أن يكون نصاً.',
            'type.in' => 'نوع السؤال يجب أن يكون أحد: نعم/لا، مقياس 1-5، مقياس 1-10، اختيار متعدد، نص، ملف، تاريخ، رقم',
            
            // Weight and scoring - الوزن والدرجات
            'weight.required' => 'حقل الوزن مطلوب.',
            'weight.integer' => 'حقل الوزن يجب أن يكون رقماً صحيحاً.',
            'weight.min' => 'الوزن يجب أن يكون 1 على الأقل.',
            'weight.max' => 'الوزن لا يتجاوز 100.',
            
            'max_score.required' => 'حقل الدرجة القصوى مطلوب.',
            'max_score.integer' => 'حقل الدرجة القصوى يجب أن يكون رقماً صحيحاً.',
            'max_score.min' => 'الدرجة القصوى يجب أن تكون 1 على الأقل.',
            'max_score.max' => 'الدرجة القصوى لا تتجاوز 100.',
            
            'passing_score.integer' => 'حقل درجة النجاح يجب أن يكون رقماً صحيحاً.',
            'passing_score.min' => 'درجة النجاح يجب أن تكون 0 على الأقل.',
            'passing_score.lte' => 'درجة النجاح يجب أن تكون أقل من أو تساوي الدرجة القصوى.',
            
            // Settings - الإعدادات
            'is_required.boolean' => 'حقل إلزامي يجب أن يكون قيمة منطقية (true/false).',
            'is_critical.boolean' => 'حقل حرج يجب أن يكون قيمة منطقية (true/false).',
            'has_attachment.boolean' => 'حقل لديه مرفق يجب أن يكون قيمة منطقية (true/false).',
            'attachment_instructions.string' => 'حقل تعليمات المرفق يجب أن يكون نصاً.',
            
            // Order and status - الترتيب والحالة
            'sort_order.integer' => 'حقل ترتيب الفرز يجب أن يكون رقماً صحيحاً.',
            'sort_order.min' => 'ترتيب الفرز يجب أن يكون 0 على الأقل.',
            
            'is_active.boolean' => 'حقل نشط يجب أن يكون قيمة منطقية (true/false).',
            
            // Options validation for multiple choice - التحقق من الخيارات للاختيار المتعدد
            'options.required' => 'حقل الخيارات مطلوب لنوع السؤال "اختيار متعدد".',
            'options.array' => 'حقل الخيارات يجب أن يكون مصفوفة.',
            'options.min' => 'يجب توفير خيارين على الأقل للاختيار المتعدد.',
            
            'options.*.value.required' => 'قيمة الخيار مطلوبة.',
            'options.*.value.string' => 'قيمة الخيار يجب أن تكون نصاً.',
            'options.*.value.max' => 'قيمة الخيار لا تتجاوز 255 حرفاً.',
            
            'options.*.score.required' => 'درجة الخيار مطلوبة.',
            'options.*.score.integer' => 'درجة الخيار يجب أن تكون رقماً صحيحاً.',
            'options.*.score.min' => 'درجة الخيار يجب أن تكون 0 على الأقل.',
            'options.*.score.max' => 'درجة الخيار لا تتجاوز 100.',
            
            'correct_answer.string' => 'الإجابة الصحيحة يجب أن تكون نصاً.',
            'correct_answer.max' => 'الإجابة الصحيحة لا تتجاوز 255 حرفاً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'checklist_id' => 'معرف قائمة التحقق',
            'criteria_id' => 'معرف المعيار',
            'question_text' => 'نص السؤال',
            'description' => 'الوصف',
            'help_text' => 'نص المساعدة',
            'type' => 'نوع السؤال',
            'weight' => 'الوزن',
            'max_score' => 'الدرجة القصوى',
            'passing_score' => 'درجة النجاح',
            'is_required' => 'إلزامي',
            'is_critical' => 'حرج',
            'has_attachment' => 'لديه مرفق',
            'attachment_instructions' => 'تعليمات المرفق',
            'sort_order' => 'ترتيب الفرز',
            'is_active' => 'نشط',
            'options' => 'الخيارات',
            'options.*.value' => 'قيمة الخيار',
            'options.*.score' => 'درجة الخيار',
            'correct_answer' => 'الإجابة الصحيحة',
        ];
    }



}
