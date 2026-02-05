<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visit_id' => $this->visit_id,
            'question_id' => $this->question_id,
            
            // الإجابة
            'answer_value' => $this->answer_value,
            'score' => (float) $this->score,
            'max_score' => (int) $this->max_score,
            'score_percentage' => $this->max_score > 0 ? ($this->score / $this->max_score) * 100 : 0,
            'is_correct' => $this->is_correct,
            'is_correct_text' => $this->is_correct === null ? 'غير محدد' : ($this->is_correct ? 'صحيح' : 'خطأ'),
            
            // ملاحظات
            'comment' => $this->comment,
            'auditor_comment' => $this->auditor_comment,
            'corrective_action' => $this->corrective_action,
            'corrective_deadline' => $this->corrective_deadline?->format('Y-m-d'),
            'corrective_deadline_formatted' => $this->corrective_deadline?->format('Y-m-d'),
            'is_overdue' => $this->isOverdue(),
            
            // حالة التصحيح
            'correction_status' => $this->correction_status,
            'correction_status_text' => $this->getCorrectionStatusText(),
            'corrected_at' => $this->corrected_at?->format('Y-m-d | h:i:s A'),
            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at?->format('Y-m-d | h:i:s A'),
            
            // التواريخ
            'created_at' => $this->created_at?->format('Y-m-d | h:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:i:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:i:s A'),
            
            // العلاقات
            'visit' => new VisitResource($this->whenLoaded('visit')),
            'question' => new QuestionResource($this->whenLoaded('question')),
            'verifier' => new UserResource($this->whenLoaded('verifier')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }

    private function getCorrectionStatusText(): string
    {
        return match ($this->correction_status) {
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'verified' => 'تم التحقق',
            'waived' => 'معفى',
            default => 'غير معروف',
        };
    }

    private function isOverdue(): bool
    {
        if (!$this->corrective_deadline) {
            return false;
        }
        
        return $this->corrective_deadline < now()->toDateString() 
            && !in_array($this->correction_status, ['completed', 'verified', 'waived']);
    }
}
