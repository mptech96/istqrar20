<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

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

            'start_latitude' => $this->start_latitude,
            'start_longitude' => $this->start_longitude,
            'end_latitude' => $this->end_latitude,
            'end_longitude' => $this->end_longitude,
            'location_url' => $this->location_url,
            'location_address' => $this->location_address,
            
            // النتائج
            'total_score' => (float) $this->total_score,
            'max_possible_score' => (int) $this->max_possible_score,
            'percentage' => (float) $this->percentage,
            'percentage_formatted' => $this->percentage ? number_format($this->percentage, 2) . '%' : null,
            'category' => $this->category,
            'category_text' => $this->getCategoryText(),
            
            // الحالة
            'status' => $this->getStatus(),
            'status_text' => $this->getStatusText(),
            
            // الوقت المتبقي بالثواني
            'remaining_seconds' => $this->getRemainingSeconds(),
            
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

    /**
     * حساب الحالة مع مراعاة انتهاء الوقت
     */
    private function getStatus(): string
    {
        // إذا كانت الزيارة مكتملة أو ملغاة أو معتمدة أو مرفوضة، نرجع الحالة الأصلية
        if (in_array($this->status, ['completed', 'cancelled', 'approved', 'rejected'])) {
            return $this->status;
        }
        
        // إذا انتهى الوقت المحدد للزيارة ولم تكتمل
        $remainingSeconds = $this->getRemainingSeconds();
        if ($remainingSeconds !== null && $remainingSeconds <= 0) {
            return 'incomplete'; // غير مكتملة
        }
        
        // غير ذلك نرجع الحالة الأصلية
        return $this->status;
    }

    /**
     * حساب الوقت المتبقي لانتهاء الزيارة بالثواني
     */
    private function getRemainingSeconds(): ?int
    {
        // إذا كانت الزيارة مكتملة أو ملغاة أو معتمدة، لا حاجة لحساب الوقت
        if (in_array($this->status, ['completed', 'cancelled', 'approved', 'rejected'])) {
            return null;
        }
        
        // إذا لم يبدأ الوقت الفعلي بعد، أو لا يوجد وقت محدد
        if (!$this->scheduled_date || !$this->scheduled_time || !$this->estimated_duration) {
            return null;
        }
        
        try {
            // وقت بدء الزيارة المتوقع
            $startDateTime = Carbon::parse($this->scheduled_date->format('Y-m-d') . ' ' . $this->scheduled_time);
            
            // وقت انتهاء الزيارة المتوقع
            $endDateTime = $startDateTime->copy()->addMinutes($this->estimated_duration);
            
            $now = Carbon::now();
            
            // الوقت المتبقي بالثواني (قد يكون سالباً إذا انتهى الوقت)
            return (int) $now->diffInSeconds($endDateTime, false);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getStatusText(): string
    {
        $status = $this->getStatus();
        
        return match ($status) {
            'draft' => 'مسودة',
            'scheduled' => 'مجدولة',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            'under_review' => 'قيد المراجعة',
            'approved' => 'معتمدة',
            'rejected' => 'مرفوضة',
            'incomplete' => 'غير مكتملة (انتهى الوقت)',
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