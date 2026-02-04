<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CriteriaRangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'criteria_id' => $this->criteria_id,
            'name' => $this->name,
            'min_score' => (int) $this->min_score,
            'max_score' => (int) $this->max_score,
            'score_range' => "{$this->min_score} - {$this->max_score}",
            'color' => $this->color,
            'description' => $this->description,
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d | h:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:i:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:i:s A'),
            
            // Relationships
            'criteria' => new CriteriaResource($this->whenLoaded('criteria')),
        ];
    }
}
