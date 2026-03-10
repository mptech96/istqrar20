<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\V1\Visit;

class StartVisitRequest extends FormRequest
{
    private ?Visit $visit = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            // حقول الموقع - مطلوبة عند بدء الزيارة
            'start_latitude' => ['required', 'numeric', 'between:-90,90'],
            'start_longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_url' => ['nullable', 'url', 'max:500'],
            'location_address' => ['nullable', 'string', 'max:1000'],
            
            // حقل التاريخ - اختياري
            'visit_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];

        // إضافة التحقق من وقت بدء الزيارة
        $visit = $this->getVisit();
        if ($visit && $visit->scheduled_date) {
            $rules['start_time_validation'] = [
                'required',
                function ($attribute, $value, $fail) use ($visit) {
                    $now = now();
                    $scheduledDateTime = $visit->scheduled_date;
                    
                    // إذا كان هناك وقت محدد مسبقاً
                    if ($visit->scheduled_time) {
                        $scheduledDateTime = $visit->scheduled_date . ' ' . $visit->scheduled_time;
                        $scheduledDateTime = \Carbon\Carbon::parse($scheduledDateTime);
                        
                        // حساب الفرق بالدقائق
                        $minutesDifference = $now->diffInMinutes($scheduledDateTime, false);
                        
                        // السماح ببدء الزيارة قبل الموعد بـ 15 دقيقة أو بعد الموعد بـ 30 دقيقة
                        $earlyLimit = -15; // يمكن البدء قبل الموعد بـ 15 دقيقة
                        $lateLimit = 30;   // يمكن البدء بعد الموعد بـ 30 دقيقة
                        
                        if ($minutesDifference < $earlyLimit) {
                            $fail("لا يمكن بدء الزيارة قبل الموعد المحدد بأكثر من 15 دقيقة. الوقت المحدد للزيارة: " . $scheduledDateTime->format('Y-m-d H:i'));
                        }
                        
                        if ($minutesDifference > $lateLimit) {
                            $fail("لا يمكن بدء الزيارة بعد الموعد المحدد بأكثر من 30 دقيقة. الوقت المحدد للزيارة: " . $scheduledDateTime->format('Y-m-d H:i'));
                        }
                    } else {
                        // إذا كان هناك تاريخ فقط بدون وقت محدد
                        if ($now->format('Y-m-d') != $scheduledDateTime) {
                            $fail("يجب بدء الزيارة في التاريخ المحدد: " . $scheduledDateTime);
                        }
                    }
                },
            ];
        }

        return $rules;
    }

    /**
     * Get the visit instance from route parameter.
     */
    private function getVisit(): ?Visit
    {
        if ($this->visit === null) {
            $visitId = $this->route('visit'); // Assuming the route parameter is 'visit'
            if ($visitId) {
                $this->visit = Visit::find($visitId);
            }
        }
        
        return $this->visit;
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

        // إضافة الوقت الحالي للتحقق
        $this->merge([
            'start_time_validation' => now(),
        ]);
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // تحديث وقت البدء الفعلي
        $visit = $this->getVisit();
        if ($visit) {
            $visit->actual_start_time = now();
            $visit->status = 'in_progress';
            $visit->save();
        }
    }
}