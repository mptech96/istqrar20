<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Illuminate\Database\Eloquent\Builder;

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

        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'location_url',
        'location_address',

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
    
    protected static function booted()
    {
        static::addGlobalScope('visit', function (Builder $builder) {
            if (auth()->check()){
                
                $user = auth()->user();
                
                if($user->role === 'company_admin') {
                    $builder->whereHas('company')
                        ->where('status', 'completed');
                }
                elseif($user->role === 'branch_manager') {
                    $builder->where('branch_id', $user->branch_id)
                        ->where('status', 'completed');
                }
                elseif($user->role === 'auditor') {
                    $builder->where('auditor_id', $user->id);
                }
                elseif($user->role === 'super_admin') {
                    $builder->whereRaw('1 = 1');
                }
                else {
                    $builder->whereRaw('1 = 0');
                }
            }
            else {
                $builder->whereRaw('1 = 0');
            }
        });

    }
    
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
        if ($percentage >= 90) return 'ممتاز';
        if ($percentage >= 80) return 'جيد جدا';
        if ($percentage >= 70) return 'جيد';
        if ($percentage >= 60) return 'مقبول';
        return 'ضعيف';
    }



    /**
     * الحصول على تفاصيل النقاط لكل معيار في الزيارة
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCriteriaScores(): \Illuminate\Support\Collection
    {
        // جلب جميع إجابات الزيارة مع علاقات الأسئلة والمعايير ونطاقات المعايير
        $answers = $this->answers()
            ->with(['question.criteria.ranges'])
            ->get();

        // تجميع النتائج حسب المعيار
        $criteriaScores = [];

        foreach ($answers as $answer) {
            $question = $answer->question;
            if (!$question || !$question->criteria) {
                continue; // تجاهل الأسئلة غير المرتبطة بمعيار
            }

            $criteria = $question->criteria;
            $criteriaId = $criteria->id;

            if (!isset($criteriaScores[$criteriaId])) {
                $criteriaScores[$criteriaId] = [
                    'criteria_name' => $criteria->name,
                    'total_earned' => 0,
                    'total_possible' => 0,
                    'weight' => $criteria->weight,
                ];
            }

            $criteriaScores[$criteriaId]['total_earned'] += $answer->score ?? 0;
            $criteriaScores[$criteriaId]['total_possible'] += $question->max_score ?? 0;
        }

        // حساب النسبة المئوية وتحديد المستوى لكل معيار
        $results = collect();
        foreach ($criteriaScores as $data) {
            $percentage = $data['total_possible'] > 0
                ? round(($data['total_earned'] / $data['total_possible']) * 100, 2)
                : 0;

            // البحث عن نطاق المستوى المناسب
            $level = $this->getCriteriaLevel($percentage, $data['criteria_name']);

            $results->push([
                'criteria_name' => $data['criteria_name'],
                'earned_points' => $data['total_earned'],
                'possible_points' => $data['total_possible'],
                'percentage' => $percentage,
                'level' => $level['name'],
                'level_color' => $level['color'],
                'weight' => $data['weight'],
            ]);
        }

        return $results;
    }

    /**
     * تحديد مستوى المعيار بناءً على النسبة المئوية والنطاقات المسجلة
     *
     * @param float $percentage
     * @param string $criteriaName
     * @return array
     */
    private function getCriteriaLevel(float $percentage, string $criteriaName): array
    {
        // البحث عن النطاق المناسب من جدول criteria_ranges
        $range = \App\Models\V1\CriteriaRange::whereHas('criteria', function ($q) use ($criteriaName) {
            $q->where('name', $criteriaName);
        })->where('min_score', '<=', $percentage)
        ->where('max_score', '>=', $percentage)
        ->first();

        if ($range) {
            return [
                'name' => $range->name,
                'color' => $range->color ?? 'secondary',
            ];
        }

        // القيم الافتراضية في حال عدم وجود نطاقات محددة
        if ($percentage >= 90) {
            return ['name' => 'ممتاز', 'color' => 'success'];
        } elseif ($percentage >= 75) {
            return ['name' => 'جيد جداً', 'color' => 'primary'];
        } elseif ($percentage >= 60) {
            return ['name' => 'جيد', 'color' => 'info'];
        } elseif ($percentage >= 50) {
            return ['name' => 'مقبول', 'color' => 'warning'];
        } else {
            return ['name' => 'ضعيف', 'color' => 'danger'];
        }
    }

}
