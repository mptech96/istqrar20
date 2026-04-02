<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'commercial_registration' => $this->commercial_registration,
            'city' => $this->city,
            'address' => $this->address,
            'contact_person' => $this->contact_person,
            'logo' => $this->logo ? asset('uploads/companies/logos/' . $this->logo) : null,
            'max_allowed_users' => $this->max_allowed_users,
            'max_allowed_branches' => $this->max_allowed_branches,

            'subscription_start' => $this->subscription_start,
            'subscription_end' => $this->subscription_end,
            'subscription_status' => $this->subscription_status,
            'total_branches' => $this->total_branches,
            'overall_score' => (float) $this->overall_score,
            'category' => $this->category,
            'status' => (bool) $this->status,
            'status_text' => $this->status ? 'نشط' : 'غير نشط',
            'is_verified' => (bool) $this->is_verified,
            'ai_support' => (bool) $this->ai_support,
            'is_subscription_active' => $this->is_subscription_active,
            'average_score' => (float) round($this->average_score, 2),
            'remaining_subscription_days' => $this->getRemainingSubscriptionDays(),
            'created_at' => $this->created_at?->format('Y-m-d | h:m:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:m:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:m:s A'),
            
            // Relationships
            //'branches' => BranchResource::collection($this->whenLoaded('branches')),
            'manager' => new UserResource($this->whenLoaded('manager')),
            //'visits' => VisitResource::collection($this->whenLoaded('visits')),
        ];
    }
}
