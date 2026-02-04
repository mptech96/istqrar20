<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CriteriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'weight' => (float) $this->weight,
            'is_active' => (bool) $this->is_active,
            'is_active_text' => $this->is_active ? 'نشط' : 'غير نشط',
            'sort_order' => $this->sort_order,
            
            // Creator
            'created_by' => $this->created_by,
            'created_by_user' => new UserResource($this->whenLoaded('creator')),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d | h:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:i:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:i:s A'),
            
            // Relationships
            'ranges' => CriteriaRangeResource::collection($this->whenLoaded('ranges')),
        ];
    }
}
