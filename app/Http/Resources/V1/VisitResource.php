<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // الجهات المعنية
            'branch_id' => $this->branch_id,
            'auditor_id' => $this->auditor_id,
            'checklist_id' => $this->checklist_id,
            
            // التواريخ
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'scheduled_time' => $this->scheduled_time,
            'actual_start_time' => $this->actual_start_time?->format('Y-m-d | h:i:s A'),
            'actual_end_time' => $this->actual_end_time?->format('Y-m-d | h:i:s A'),
            'visit_date' => $this->visit_date?->format('Y-m-d'),
            'completed_at' => $this->completed_at?->format('Y-m-d | h:i:s A'),
            
            // التوقيت
            'estimated_duration' => $this->estimated_duration,
            'estimated_duration_formatted' => $this->estimated_duration ? $this->formatDuration($this->estimated_duration) : null,
            'actual_duration' => $this->actual_duration,
            'actual_duration_formatted' => $this->actual_duration ? $this->formatDuration($this->actual_duration) : null,
            
            // النتائج
            'total_score' => (float) $this->total_score,
            'max_possible_score' => (int) $this->max_possible_score,
            'percentage' => (float) $this->percentage,
            'percentage_formatted' => $this->percentage ? number_format($this->percentage, 2) . '%' : null,
            'category' => $this->category,
            'category_text' => $this->getCategoryText(),
            
            // الحالة
            'status' => $this->status,
            'status_text' => $this->getStatusText(),
            
            // الموافقات
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->format('Y-m-d | h:i:s A'),
            'rejection_reason' => $this->rejection_reason,
            
            // ملاحظات
            'auditor_notes' => $this->auditor_notes,
            'branch_feedback' => $this->branch_feedback,
            'internal_notes' => $this->internal_notes,
            
            // التواريخ
            'created_at' => $this->created_at?->format('Y-m-d | h:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:i:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:i:s A'),
            
            // العلاقات
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'auditor' => new UserResource($this->whenLoaded('auditor')),
            'checklist' => new ChecklistResource($this->whenLoaded('checklist')),
            'approver' => new UserResource($this->whenLoaded('approver')),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            
            // الإحصائيات
            'answers_count' => $this->whenLoaded('answers', function () {
                return $this->answers->count();
            }),
            'completed_answers_count' => $this->whenLoaded('answers', function () {
                return $this->answers->whereNotNull('score')->count();
            }),
        ];
    }

    private function getStatusText(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'scheduled' => 'مجدولة',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            'under_review' => 'قيد المراجعة',
            'approved' => 'معتمدة',
            'rejected' => 'مرفوضة',
            default => 'غير معروف',
        };
    }

    private function getCategoryText(): string
    {
        return match ($this->category) {
            'A' => 'ممتاز (90% فما فوق)',
            'B' => 'جيد جداً (80% - 89%)',
            'C' => 'جيد (70% - 79%)',
            'D' => 'مقبول (60% - 69%)',
            'F' => 'ضعيف (أقل من 60%)',
            default => 'غير مصنف',
        };
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return $hours . ' ساعة' . ($remainingMinutes > 0 ? ' و' . $remainingMinutes . ' دقيقة' : '');
        }
        return $minutes . ' دقيقة';
    }
}
