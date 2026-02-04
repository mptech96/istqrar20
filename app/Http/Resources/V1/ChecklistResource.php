<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'version' => $this->version,
            
            // Target information
            'company_id' => $this->company_id,
            'industry_type' => $this->industry_type,
            
            // Settings
            'total_score' => (int) $this->total_score,
            'passing_score' => (int) $this->passing_score,
            'time_estimate' => $this->time_estimate,
            
            // Status
            'is_active' => (bool) $this->is_active,
            'is_active_text' => $this->is_active ? 'نشط' : 'غير نشط',
            'is_template' => (bool) $this->is_template,
            'is_template_text' => $this->is_template ? 'نموذج' : 'قائمة',
            
            // Statistics (computed if loaded)
            'questions_count' => $this->whenLoaded('questions', function () {
                return $this->questions->count();
            }),
            'active_questions_count' => $this->whenLoaded('questions', function () {
                return $this->questions->where('is_active', true)->count();
            }),
            
            // Creator
            'created_by' => $this->created_by,
            'created_by_user' => new UserResource($this->whenLoaded('creator')),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d | h:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:i:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:i:s A'),
            
            // Relationships
            'company' => new CompanyResource($this->whenLoaded('company')),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
