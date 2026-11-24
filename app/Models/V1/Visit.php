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
        $statistics = app(\App\Services\V1\Scoring\VisitScoringService::class)->calculate($this);

        $this->update([
            'total_score' => $statistics['total_score'],
            'max_possible_score' => $statistics['max_possible_score'],
            'percentage' => $statistics['percentage'],
            'category' => $statistics['category'],
        ]);

        return $this->refresh();
    }



    /**
     * الحصول على تفاصيل النقاط لكل معيار في الزيارة
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCriteriaScores(): \Illuminate\Support\Collection
    {
        $scoringService = app(\App\Services\V1\Scoring\VisitScoringService::class);
        $questions = $this->checklist
            ? $this->checklist->questions()
                ->where('is_active', true)
                ->with(['criteria.ranges'])
                ->get()
            : collect();

        $answers = $this->answers()
            ->whereIn('question_id', $questions->pluck('id'))
            ->get()
            ->keyBy('question_id');

        $criteriaScores = [];

        foreach ($questions as $question) {
            if (!$question->criteria || in_array($question->type, ['text', 'date', 'file'], true)) {
                continue;
            }

            $criteria = $question->criteria;
            $criteriaId = $criteria->id;

            if (!isset($criteriaScores[$criteriaId])) {
                $criteriaScores[$criteriaId] = [
                    'criteria_id' => $criteriaId,
                    'criteria_name' => $criteria->name,
                    'total_earned' => 0,
                    'total_possible' => 0,
                    'weight' => $criteria->weight,
                ];
            }

            $answer = $answers->get($question->id);
            $questionMaximum = (float) $scoringService->maximumAchievableScore($question);
            $answerMaximum = $answer && $answer->max_score !== null
                ? (float) $answer->max_score
                : $questionMaximum;

            $criteriaScores[$criteriaId]['total_earned'] += $answer
                ? min($answerMaximum, max(0, (float) $answer->score))
                : 0;
            $criteriaScores[$criteriaId]['total_possible'] += $questionMaximum;
        }

        $results = collect();
        foreach ($criteriaScores as $data) {
            $percentage = $data['total_possible'] > 0
                ? round(($data['total_earned'] / $data['total_possible']) * 100, 2)
                : 0;

            $level = $this->getCriteriaLevel($percentage, (int) $data['criteria_id']);

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
     * @param int $criteriaId
     * @return array
     */
    private function getCriteriaLevel(float $percentage, int $criteriaId): array
    {
        // ربط نطاق النتيجة بمعرف المعيار نفسه لتجنب اختيار نطاق معيار آخر.
        $range = \App\Models\V1\CriteriaRange::where('criteria_id', $criteriaId)
            ->where('min_score', '<=', $percentage)
            ->where('max_score', '>=', $percentage)
            ->whereNull('deleted_at')
            ->orderBy('min_score', 'desc')
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
