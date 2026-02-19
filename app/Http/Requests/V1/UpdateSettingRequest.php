<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role == 'super_admin';
    }

    public function rules(): array
    {
        return [
            'group' => ['sometimes', 'string', 'max:100'],
            'key' => ['sometimes', 'string', 'max:100', Rule::unique('settings')->where(function ($query) {
                return $query->where('group', $this->group ?? $this->setting->group);
            })->ignore($this->setting->id)],
            'value' => ['nullable'],
            'type' => ['sometimes', Rule::in(['string', 'number', 'boolean', 'json', 'array', 'text'])],
            'data_type' => ['nullable', 'string', 'max:50'],
            'validation_rules' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_public' => ['nullable', 'boolean'],
            'is_encrypted' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'group.max' => 'اسم المجموعة يجب ألا يتجاوز 100 حرف',
            'key.unique' => 'الإعداد موجود مسبقاً في هذه المجموعة',
            'type.in' => 'نوع الإعداد غير صالح',
        ];
    }
}
