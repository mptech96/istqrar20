<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class VisitAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'visit_id'                => $this->visit_id,
            'branch'                  => $this->branch->name,
            'auditor'                  => $this->auditor->name,
            'company'                 => $this->company->name,

            'analysis_type'           => $this->analysis_type,
            'analysis_date'           => $this->analysis_date?->toDateString(),
            'generated_by'            => $this->generated_by,

            'total_questions'         => $this->total_questions,
            'answered_questions'      => $this->answered_questions,
            'correct_answers'         => $this->correct_answers,
            'incorrect_answers'       => $this->incorrect_answers,

            'critical_errors'         => $this->critical_errors,
            'major_errors'            => $this->major_errors,
            'minor_errors'            => $this->minor_errors,

            'total_score'             => $this->total_score,
            'lost_points'             => $this->lost_points,
            'potential_score'         => $this->potential_score,
            'score_percentage'        => $this->score_percentage,
            'improvement_rate'        => $this->improvement_rate,

            'summary'                 => $this->summary,
            'strengths'               => $this->strengths,
            'weaknesses'              => $this->weaknesses,
            'opportunities'           => $this->opportunities,
            'threats'                 => $this->threats,

            'recommendations'         => $this->recommendations,
            'priority_recommendations'=> $this->priority_recommendations,
            'improvement_plan'        => $this->improvement_plan,

            'previous_visit_id'       => $this->previous_visit_id,
            'error_categories'        => $this->error_categories,
            'top_error_category'      => $this->top_error_category,

            'status'                  => $this->status,
            'reviewed_by'             => $this->reviewed_by,
            'reviewed_at'             => $this->reviewed_at?->format('Y-m-d | h:i:s A'),

            // Relations
            'visit'                   => $this->whenLoaded('visit'),
            //'branch'                  => $this->whenLoaded('branch'),
            //'company'                 => $this->whenLoaded('company'),
            'generated_by_user'       => $this->whenLoaded('generatedBy'),
            'reviewed_by_user'        => $this->whenLoaded('reviewedBy'),
            'previous_visit'          => $this->whenLoaded('previousVisit'),

            'has_critical_issues'     => $this->hasCriticalIssues(),
            'criteria_scores'     => $this->visit->getCriteriaScores(),
        
            'created_at' => $this->created_at?->format('Y-m-d | h:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:i:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:i:s A'),
            
        ];
    }
}