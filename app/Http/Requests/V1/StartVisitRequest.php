<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StartVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // حقول الموقع - مطلوبة عند بدء الزيارة
            'start_latitude' => ['required', 'numeric', 'between:-90,90'],
            'start_longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_url' => ['nullable', 'url', 'max:500'],
            'location_address' => ['nullable', 'string', 'max:1000'],
            
            // حقل التاريخ - اختياري
            'visit_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            // رسائل حقول الموقع
            'start_latitude.required' => 'خط العرض مطلوب لتسجيل موقع بدء الزيارة.',
            'start_latitude.numeric' => 'خط العرض يجب أن يكون رقماً.',
            'start_latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90.',
            
            'start_longitude.required' => 'خط الطول مطلوب لتسجيل موقع بدء الزيارة.',
            'start_longitude.numeric' => 'خط الطول يجب أن يكون رقماً.',
            'start_longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180.',
            
            'location_url.url' => 'رابط الموقع يجب أن يكون رابطاً صحيحاً.',
            'location_url.max' => 'رابط الموقع لا يتجاوز 500 حرف.',
            
            'location_address.max' => 'العنوان لا يتجاوز 1000 حرف.',
            
            // رسالة حقل التاريخ
            'visit_date.before_or_equal' => 'تاريخ الزيارة يجب أن يكون اليوم أو تاريخ سابق.',
        ];
    }

    public function attributes(): array
    {
        return [
            'start_latitude' => 'خط العرض',
            'start_longitude' => 'خط الطول',
            'location_url' => 'رابط الموقع',
            'location_address' => 'العنوان',
            'visit_date' => 'تاريخ الزيارة',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // إذا لم يتم إرسال تاريخ الزيارة، استخدم تاريخ اليوم
        if (!$this->has('visit_date')) {
            $this->merge([
                'visit_date' => now(),
            ]);
        }
    }
}