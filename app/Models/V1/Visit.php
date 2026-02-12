<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Visit extends Model
{
    use HasFactory, SoftDeletes, HasRelationships;

    protected $fillable = [
        'branch_id',
        'auditor_id',
        'checklist_id',
        'scheduled_date',
        'scheduled_time',
        'actual_start_time',
        'actual_end_time',
        'visit_date',
        'completed_at',
        'estimated_duration',
        'actual_duration',
        'total_score',
        'max_possible_score',
        'percentage',
        'category',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'auditor_notes',
        'branch_feedback',
        'internal_notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'visit_date' => 'date',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'estimated_duration' => 'integer',
        'actual_duration' => 'integer',
        'total_score' => 'decimal:2',
        'max_possible_score' => 'integer',
        'percentage' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    
    public function company()
    {
        return $this->hasOneDeepFromRelations(
            $this->branch(),     
            (new Branch)->company()
        );
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }
    
    public function analysis()
    {
        return $this->hasOne(VisitAnalysis::class, 'visit_id');
    }

    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }
    
    public function questions()
    {
        return $this->hasManyDeepFromRelations(
            $this->checklist(),     
            (new Checklist)->questions()
        );
    }


    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'entity');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByAuditor($query, $auditorId)
    {
        return $query->where('auditor_id', $auditorId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDateRange($query, $fromDate, $toDate)
    {
        return $query->whereBetween('scheduled_date', [$fromDate, $toDate]);
    }

    public function getStatusText()
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

    public function getDuration()
    {
        if ($this->actual_start_time && $this->actual_end_time) {
            return $this->actual_start_time->diffInMinutes($this->actual_end_time);
        }
        
        return $this->actual_duration ?? $this->estimated_duration;
    }

    public function calculateStatistics()
    {
        $answers = $this->answers;
        
        $totalScore = $answers->sum('score');
        $maxPossibleScore = $answers->sum('max_score');
        $percentage = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;
        
        $this->update([
            'total_score' => $totalScore,
            'max_possible_score' => $maxPossibleScore,
            'percentage' => $percentage,
            'category' => $this->determineCategory($percentage),
        ]);
        
        return $this;
    }

    private function determineCategory(float $percentage): string
    {
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
    }
}
