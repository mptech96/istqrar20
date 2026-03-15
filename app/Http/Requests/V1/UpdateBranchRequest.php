<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch') ? $this->route('branch')->id : null;

        return [
            // Branch data
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:branches,email,' . $branchId],
            'phone' => ['sometimes', 'string', 'max:20'],
            
            // Location
            'city' => ['sometimes', 'string', 'max:100'],
            'address' => ['sometimes', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            
            // Statistics
            'category' => ['sometimes', 'string', 'max:255'],
            'compliance_score' => ['nullable', 'numeric', 'between:0,100'],
            'total_visits' => ['nullable', 'integer', 'min:0'],
            'last_visit_date' => ['nullable', 'date'],
            'next_scheduled_visit' => ['nullable', 'date'],
            
            
            // Manager update
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_email' => ['nullable', 'email'],
            'manager_phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90.',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180.',
            'compliance_score.between' => 'درجة الامتثال يجب أن تكون بين 0 و 100.',
            'total_visits.min' => 'عدد الزيارات لا يمكن أن يكون سالبًا.',
        ];
    }
}
