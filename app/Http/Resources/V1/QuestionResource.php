<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'checklist_id' => $this->checklist_id,
            'criteria_id' => $this->criteria_id,
            
            // Question text
            'question_text' => $this->question_text,
            'description' => $this->description,
            'help_text' => $this->help_text,
            
            // Question type
            'type' => $this->type,
            'type_text' => $this->getTypeText(),
            
            // Options for multiple choice questions
            'options' => $this->when($this->type === 'multiple_choice', function () {
                return json_decode($this->options, true);
            }),
            'correct_answer' => $this->correct_answer,
            
            // Weight and scoring
            'weight' => (int) $this->weight,
            'max_score' => (int) $this->max_score,
            'passing_score' => $this->passing_score ? (int) $this->passing_score : null,
            
            // Settings
            'is_required' => (bool) $this->is_required,
            'is_required_text' => $this->is_required ? 'مطلوب' : 'اختياري',
            'is_critical' => (bool) $this->is_critical,
            'is_critical_text' => $this->is_critical ? 'حرج' : 'عادي',
            'has_attachment' => (bool) $this->has_attachment,
            'attachment_instructions' => $this->attachment_instructions,
            
            // Order and status
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'is_active_text' => $this->is_active ? 'نشط' : 'غير نشط',
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d | h:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:i:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:i:s A'),
            
            // Relationships
            'checklist' => new ChecklistResource($this->whenLoaded('checklist')),
            'criteria' => new CriteriaResource($this->whenLoaded('criteria')),
        ];
    }

    private function getTypeText(): string
    {
        return match ($this->type) {
            'yes_no' => 'نعم/لا',
            'scale_1_5' => 'مقياس من 1 إلى 5',
            'scale_1_10' => 'مقياس من 1 إلى 10',
            'multiple_choice' => 'اختيار متعدد',
            'text' => 'نص',
            'file' => 'ملف',
            'date' => 'تاريخ',
            'number' => 'رقم',
            default => 'غير معروف',
        };
    }
}
