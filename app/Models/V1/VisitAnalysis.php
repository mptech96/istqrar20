<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Illuminate\Database\Eloquent\Builder;

class VisitAnalysis extends Model
{
    use SoftDeletes, HasRelationships;

    protected $table = 'visit_analyses';

    protected $fillable = [
        'visit_id',
        'branch_id',
        'company_id',

        'analysis_type',
        'analysis_date',
        'generated_by',

        'total_questions',
        'answered_questions',
        'correct_answers',
        'incorrect_answers',

        'critical_errors',
        'major_errors',
        'minor_errors',

        'total_score',
        'lost_points',
        'potential_score',

        'summary',
        'strengths',
        'weaknesses',
        'opportunities',
        'threats',

        'recommendations',
        'priority_recommendations',
        'improvement_plan',

        'previous_visit_id',
        'improvement_rate',

        'error_categories',
        'top_error_category',

        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'analysis_date' => 'date',
        'reviewed_at'   => 'datetime',

        'total_score'     => 'decimal:2',
        'lost_points'     => 'decimal:2',
        'potential_score' => 'decimal:2',
        'improvement_rate'=> 'decimal:2',

        'recommendations'        => 'array',
        'priority_recommendations'=> 'array',
        'error_categories'       => 'array',
    ];
    
    protected static function booted()
    {
        static::addGlobalScope('statistics', function (Builder $builder) {
            if (auth()->check()){
                
                $user = auth()->user();
                
                if($user->role === 'company_admin') {
                    $builder->whereHas('visit');
                }
                elseif($user->role === 'branch_manager') {
                    $builder->whereHas('visit');
                }
                elseif($user->role === 'auditor') {
                    $builder->whereHas('visit');
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
    
    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
    
    public function auditor()
    {
        return $this->hasOneDeepFromRelations(
            $this->visit(),     
            (new Visit)->auditor()
        );
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function previousVisit()
    {
        return $this->belongsTo(Visit::class, 'previous_visit_id');
    }
    
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
    
    public function getScorePercentageAttribute(): float
    {
        if ($this->potential_score == 0) {
            return 0;
        }

        return round(($this->total_score / $this->potential_score) * 100, 2);
    }

    public function hasCriticalIssues(): bool
    {
        return $this->critical_errors > 0;
    }
}