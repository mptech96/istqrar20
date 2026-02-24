<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiSuggestionResource extends JsonResource
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
            'metric_name' => $this->metric_name,
            'record_date' => $this->record_date->format('Y-m-d'),
            'period_type' => $this->period_type,
            
            // القيم
            'current_value' => (float) $this->current_value,
            'previous_value' => (float) $this->previous_value,
            'percentage_change' => (float) $this->percentage_change,
            'trend_direction' => $this->trend_direction,
            'trend_text' => $this->trend_text,
            
            // التصنيف
            'severity' => $this->severity,
            'severity_text' => $this->severity_text,
            'severity_color' => $this->severity_color,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            
            // مخرجات الذكاء الاصطناعي
            'suggestion' => $this->suggestion,
            'summary' => $this->summary,
            'confidence' => (float) $this->confidence,
            'confidence_text' => $this->confidence_text,
            
            // بيانات التحليل المتقدمة
            'full_analysis' => $this->full_analysis,
            'recommendations' => $this->recommendations,
            'action_plan' => $this->action_plan,
            'risks' => $this->risks,
            'immediate_actions' => $this->immediate_actions,
            'quick_wins' => $this->quick_wins,
            
            // بيانات النموذج
            'ai_provider' => $this->ai_provider,
            'ai_model' => $this->ai_model,
            'tokens_used' => $this->tokens_used,
            'processing_time_ms' => $this->processing_time,
            'processing_time_seconds' => $this->processing_time_seconds,
            'estimated_cost_usd' => (float) $this->estimated_cost,
            
            // متابعة التنفيذ
            'is_applied' => (bool) $this->is_applied,
            'applied_at' => $this->applied_at?->toIso8601String(),
            
            // ملاحظات
            'admin_notes' => $this->admin_notes,
            
            // العلاقات
            'statistic' => new StatisticResource($this->whenLoaded('statistic')),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'applied_by' => new UserResource($this->whenLoaded('appliedBy')),
            
            // التواريخ
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

    }
}
