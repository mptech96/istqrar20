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

    public function messages(): array
    {
        return [
            'type.in' => 'نوع السؤال غير صحيح. الأنواع المسموحة: yes_no, scale_1_5, scale_1_10, multiple_choice, text, file, date, number',
            'max_score.max' => 'الدرجة العظمى يجب أن تكون أقل من أو تساوي 100.',
            'passing_score.lte' => 'درجة النجاح يجب أن تكون أقل من أو تساوي الدرجة العظمى.',
            'options.required' => 'يجب إضافة خيارات للأسئلة متعددة الاختيارات.',
            'options.*.value.required' => 'قيمة الخيار مطلوبة.',
            'options.*.score.required' => 'درجة الخيار مطلوبة.',
        ];
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

    public function attributes(): array
    {
        return [
            'question_text' => 'نص السؤال',
            'type' => 'نوع السؤال',
            'weight' => 'الوزن',
            'max_score' => 'الدرجة العظمى',
            'passing_score' => 'درجة النجاح',
        ];
    }
}
