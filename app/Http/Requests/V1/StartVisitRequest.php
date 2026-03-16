<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\V1\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

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
        ];

        // إضافة التحقق من وقت بدء الزيارة
        $visit = $this->getVisit();
        if ($visit && $visit->scheduled_date) {
            $rules['start_time_validation'] = [
                'required',
                function ($attribute, $value, $fail) use ($visit) {
                    $now = now();
                    $scheduledDate = $visit->scheduled_date instanceof Carbon
                        ? $visit->scheduled_date
                        : Carbon::parse($visit->scheduled_date);

                    // إذا كان هناك وقت محدد مسبقاً
                    if ($visit->scheduled_time) {
                        // دمج التاريخ والوقت
                        $scheduledDateTime = Carbon::parse(
                            $scheduledDate->toDateString() . ' ' . $visit->scheduled_time
                        );

                        // حساب الفرق بالدقائق
                        $minutesDifference = $now->diffInMinutes($scheduledDateTime, false) * -1;

                        // السماح ببدء الزيارة قبل الموعد بـ 10 دقيقة أو بعد الموعد بـ 30 دقيقة
                        $earlyLimit = -10; // يمكن البدء قبل الموعد بـ 10 دقيقة
                        $lateLimit = 30;   // يمكن البدء بعد الموعد بـ 30 دقيقة

                        if ($minutesDifference < $earlyLimit) {
                            $fail("لا يمكن بدء الزيارة قبل الموعد المحدد بأكثر من 10 دقيقة. الوقت المحدد للزيارة: " . $scheduledDateTime->format('Y-m-d H:i'));
                        }

                        if ($minutesDifference > $lateLimit) {
                            $fail("لا يمكن بدء الزيارة بعد الموعد المحدد بأكثر من 30 دقيقة. الوقت المحدد للزيارة: " . $scheduledDateTime->format('Y-m-d H:i'));
                        }
                    } else {
                        // إذا كان هناك تاريخ فقط بدون وقت محدد
                        if ($now->toDateString() !== $scheduledDate->toDateString()) {
                            $fail("يجب بدء الزيارة في التاريخ المحدد: " . $scheduledDate->toDateString());
                        }
                    }
                },
            ];

             $rules['location_distance_validation'] = [
                'required',
                function ($attribute, $value, $fail) use ($visit) {
                    // التحقق من وجود إحداثيات الموقع في الطلب
                    if (!$this->has('start_latitude') || !$this->has('start_longitude')) {
                        return; // سيتم التحقق منها بواسطة القواعد الأخرى
                    }

                    $userLat = $this->input('start_latitude');
                    $userLng = $this->input('start_longitude');
                    $branch = $visit->branch;
                    // التحقق من وجود إحداثيات الموقع في نموذج الزيارة
                    if (!$branch->latitude || !$branch->longitude) {
                        $fail("عذراً، لم يتم تحديد إحداثيات موقع الفرع '" . ($branch->name ?? 'الفرع') . "' بعد. الرجاء التواصل مع الإدارة لتحديث بيانات موقع الفرع والمحاولة مرة أخرى.");
                        return; // لا يوجد موقع محدد للزيارة للمقارنة
                    }

                    // حساب المسافة باستخدام صيغة Haversine
                    $distance = $this->calculateDistance(
                        $userLat, 
                        $userLng, 
                        $branch->latitude, 
                        $branch->longitude
                    );

                    // السماح بمسافة 1 كيلومتر (1000 متر)
                    $maxDistance = 1; // كيلومتر

                    if ($distance > $maxDistance) {
                        $fail("أنت بعيد عن موقع الزيارة المحدد بمسافة " . number_format($distance, 2) . " كم. يجب أن تكون على بعد كيلومتر واحد كحد أقصى لبدء الزيارة.");
                    }
                },
            ];
        }

        return $rules;
    }
    
    /**
     * Calculate distance between two points using Haversine formula
     * 
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in kilometers
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        }

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        
        // Convert miles to kilometers
        return $miles * 1.609344;
    }
    /**
     * Get the visit instance from route parameter safely.
     */
    private function getVisit(): ?Visit
    {
        if ($this->visit !== null) {
            return $this->visit;
        }

        $visitParam = $this->route('visit'); // Assuming the route parameter is 'visit'

        if ($visitParam instanceof Visit) {
            // Route Model Binding أعاد الكائن مباشرة
            $this->visit = $visitParam;
        } elseif (is_numeric($visitParam) || is_string($visitParam)) {
            // إذا كان ID فقط (في حال عدم استخدام model binding)
            $this->visit = Visit::find($visitParam);
        } elseif ($visitParam instanceof Collection && $visitParam->isNotEmpty()) {
            // في حالة استثنائية إذا أعاد Collection (نأخذ الأول)
            $this->visit = $visitParam->first();
        } else {
            $this->visit = null;
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
            'location_url.max' => 'رابط الموقع لا يتجاوز 300 حرف.',

            'location_address.max' => 'العنوان لا يتجاوز 1000 حرف.',

        ];
    }

    public function attributes(): array
    {
        return [
            'start_latitude' => 'خط العرض',
            'start_longitude' => 'خط الطول',
            'location_url' => 'رابط الموقع',
            'location_address' => 'العنوان',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // إذا لم يتم إرسال تاريخ الزيارة، استخدم تاريخ اليوم
        $this->merge([
            'visit_date' => now(),
        ]);

        // إضافة الوقت الحالي للتحقق (للقاعدة المخصصة)
        $this->merge([
            'start_time_validation' => now(),
            'location_distance_validation' => true
        ]);
    }

}