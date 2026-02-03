<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $baseUrl = config('app.url');
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            
            // Role and assignment
            'role' => $this->role,
            'role_text' => $this->getRoleText(),
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'department' => $this->department,
            'position' => $this->position,
            
            // Auditor specific documents
            'national_number' => $this->national_number,
            'municipality_certificate' => $this->municipality_certificate ? 
                asset('uploads/users/documents/' . $this->municipality_certificate) : null,
            'driving_license' => $this->driving_license ? 
                asset('uploads/users/documents/' . $this->driving_license) : null,
            'personal_photo' => $this->personal_photo ? 
                asset('uploads/users/personal_photos/' . $this->personal_photo) : null,
            
            // Document expiry dates
            'certificate_expiry' => $this->certificate_expiry?->format('Y-m-d'),
            'license_expiry' => $this->license_expiry?->format('Y-m-d'),
            'id_expiry' => $this->id_expiry?->format('Y-m-d'),
            
            // Document validity
            'is_certificate_valid' => (bool) $this->is_certificate_valid,
            'is_license_valid' => (bool) $this->is_license_valid,
            'is_id_valid' => (bool) $this->is_id_valid,
            
            // Personal information
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender,
            'gender_text' => $this->getGenderText(),
            
            // Account information
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d | h:i:s A'),
            'last_login_at' => $this->last_login_at?->format('Y-m-d | h:i:s A'),
            'last_login_ip' => $this->last_login_ip,
            
            // Status
            'status' => (int) $this->status,
            'status_text' => $this->getStatusText(),
            'must_change_password' => (bool) $this->must_change_password,
            
            // Creator
            'created_by' => $this->created_by,
            'created_by_user' => new UserResource($this->whenLoaded('creator')),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d | h:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:i:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:i:s A'),
            
            // Relationships
            'company' => new CompanyResource($this->whenLoaded('company')),
            //'branch' => new BranchResource($this->whenLoaded('branch')),
            //'managed_branch' => new BranchResource($this->whenLoaded('managedBranch')),
        ];
    }

    private function getRoleText(): string
    {
        return match ($this->role) {
            'super_admin' => 'المشرف العام',
            'auditor' => 'مراجع',
            'company_admin' => 'مدير شركة',
            'branch_manager' => 'مدير فرع',
            default => 'غير محدد',
        };
    }

    private function getGenderText(): string
    {
        return match ($this->gender) {
            'male' => 'ذكر',
            'female' => 'أنثى',
            'other' => 'غير محدد',
            default => 'غير محدد',
        };
    }

    private function getStatusText(): string
    {
        return match ((int) $this->status) {
            0 => 'غير نشط',
            1 => 'نشط',
            2 => 'موقوف',
            default => 'غير معروف',
        };
    }
}
