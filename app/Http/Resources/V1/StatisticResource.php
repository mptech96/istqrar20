<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatisticResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'record_date' => $this->record_date->format('Y-m-d'),
            'metric_name' => $this->metric_name,
            'metric_value' => (float) $this->metric_value,
            'period_type' => $this->period_type,
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'auditor_id' => $this->auditor_id,
            'additional_data' => $this->additional_data,
            'trend_direction' => $this->trend_direction,
            'previous_value' => $this->previous_value ? (float) $this->previous_value : null,
            'percentage_change' => $this->percentage_change ? (float) $this->percentage_change : null,
            'calculated_at' => $this->calculated_at?->toDateTimeString(),
            
            // Formatted values for convenience
            'formatted_metric_value' => $this->formatted_metric_value,
            'formatted_percentage_change' => $this->formatted_percentage_change,

            // Relationships (conditionally loaded)
            'company' => CompanyResource::make($this->whenLoaded('company')),
            'branch' => BranchResource::make($this->whenLoaded('branch')),
            'auditor' => UserResource::make($this->whenLoaded('auditor')),
            //'ai_suggestions' => AiSuggestionResource::collection($this->whenLoaded('aiSuggestions')),
        ];
    }
}
