<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $percentage = ($this->visits?->last()?->percentage ?? ($this->compliance_score ? (float) $this->compliance_score : 0.00));

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            
            // Location
            'city' => $this->city,
            'address' => $this->address,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            
            // Manager info
            'manager_user_id' => $this->manager_user_id,
            'manager_name' => $this->manager_name,
            'manager_email' => $this->manager_email,
            'manager_phone' => $this->manager_phone,
            
            // Statistics
            'compliance_score' => $percentage,
            'compliance' => $this->getCategoryText($percentage),
            'compliance_formatted' => $percentage ? number_format($percentage, 2) . '%' : null,

            //'category' => $this->category,
            'total_visits' => $this->total_visits,
            'last_visit_date' => $this->last_visit_date?->format('Y-m-d'),
            'next_scheduled_visit' => $this->next_scheduled_visit?->format('Y-m-d'),
            
            // Status
            'status' => (bool) $this->status,
            'status_text' => $this->status ? 'نشط' : 'غير نشط',

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d | h:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:i:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:i:s A'),
            
            // Relationships
            'company' => new CompanyResource($this->whenLoaded('company')),
            'manager' => new UserResource($this->whenLoaded('manager')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'visits' => VisitResource::collection($this->whenLoaded('visits')),
        ];
    }

    private function getCategoryText(float $percentage): string
    {
        if ($percentage === null) return 'غير مصنف';
        if ($percentage >= 90) return 'ممتاز';
        if ($percentage >= 80) return 'جيد جدا';
        if ($percentage >= 70) return 'جيد';
        if ($percentage >= 60) return 'مقبول';
        return 'ضعيف';
    }
}
