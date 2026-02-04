<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCriteriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Criteria data
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            
            // Criteria ranges array
            'ranges' => ['required', 'array', 'min:1'],
            'ranges.*.name' => ['required', 'string', 'max:100'],
            'ranges.*.min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'ranges.*.max_score' => ['required', 'integer', 'min:0', 'max:100'],
            'ranges.*.color' => ['nullable', 'string', 'max:10', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'ranges.*.description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'weight.max' => 'الوزن يجب أن يكون بين 0 و 100.',
            'ranges.required' => 'يجب إضافة نطاقات تقييم على الأقل.',
            'ranges.*.min_score.max' => 'الحد الأدنى للنقاط يجب أن يكون بين 0 و 100.',
            'ranges.*.max_score.max' => 'الحد الأقصى للنقاط يجب أن يكون بين 0 و 100.',
            'ranges.*.color.regex' => 'صيغة اللون غير صحيحة. استخدم صيغة HEX (#RRGGBB أو #RGB).',
            'ranges.*.min_score.lte' => 'الحد الأدنى للنقاط يجب أن يكون أقل من أو يساوي الحد الأقصى.',
            'ranges.*.max_score.gte' => 'الحد الأقصى للنقاط يجب أن يكون أكبر من أو يساوي الحد الأدنى.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('ranges')) {
                $ranges = $this->input('ranges');
                
                // Check for overlapping ranges
                $sortedRanges = collect($ranges)->sortBy('min_score');
                $previousMax = -1;
                
                foreach ($sortedRanges as $index => $range) {
                    if ($range['min_score'] > $range['max_score']) {
                        $validator->errors()->add(
                            "ranges.{$index}.min_score",
                            'الحد الأدنى يجب أن يكون أقل من أو يساوي الحد الأقصى.'
                        );
                    }
                    
                    if ($range['min_score'] <= $previousMax) {
                        $validator->errors()->add(
                            "ranges.{$index}.min_score",
                            'النطاقات متداخلة. يجب أن يكون الحد الأدنى أكبر من الحد الأقصى للنطاق السابق.'
                        );
                    }
                    
                    $previousMax = $range['max_score'];
                }
                
                // Check if ranges cover 0-100
                $firstMin = $sortedRanges->first()['min_score'] ?? 0;
                $lastMax = $sortedRanges->last()['max_score'] ?? 0;
                
                if ($firstMin > 0) {
                    $validator->errors()->add(
                        'ranges',
                        'يجب أن يبدأ النطاق الأول من 0.'
                    );
                }
                
                if ($lastMax < 100) {
                    $validator->errors()->add(
                        'ranges',
                        'يجب أن يصل النطاق الأخير إلى 100.'
                    );
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المعيار',
            'category' => 'الفئة',
            'weight' => 'الوزن',
            'ranges' => 'نطاقات التقييم',
            'ranges.*.name' => 'اسم النطاق',
            'ranges.*.min_score' => 'الحد الأدنى للنقاط',
            'ranges.*.max_score' => 'الحد الأقصى للنقاط',
            'ranges.*.color' => 'لون النطاق',
        ];
    }
}
