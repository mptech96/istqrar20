<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $checklistId = $this->route('checklist') ? $this->route('checklist')->id : null;

        return [
            // Checklist data
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:checklists,code,' . $checklistId],
            'description' => ['nullable', 'string'],
            'version' => ['nullable', 'string', 'max:20'],
            
            // Target information
            'company_id' => ['nullable', 'exists:companies,id'],
            'industry_type' => ['nullable', 'string', 'max:100'],
            
            // Settings
            'total_score' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'passing_score' => ['sometimes', 'integer', 'min:0', 'lte:total_score'],
            'time_estimate' => ['nullable', 'integer', 'min:1'],
            
            // Status
            'is_active' => ['nullable', 'boolean'],
            'is_template' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'total_score.max' => 'الدرجة الكلية يجب أن تكون أقل من أو تساوي 1000.',
            'passing_score.lte' => 'درجة النجاح يجب أن تكون أقل من أو تساوي الدرجة الكلية.',
            'code.unique' => 'كود القائمة مسجل مسبقاً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم القائمة',
            'code' => 'كود القائمة',
            'total_score' => 'الدرجة الكلية',
            'passing_score' => 'درجة النجاح',
        ];
    }
}
