<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Answer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'visit_id',
        'question_id',
        'answer_value',
        'score',
        'max_score',
        'is_correct',
        'comment',
        'auditor_comment',
        'corrective_action',
        'corrective_deadline',
        'correction_status',
        'corrected_at',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'integer',
        'is_correct' => 'boolean',
        'corrective_deadline' => 'date',
        'corrected_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
    
    protected static function booted()
    {
        static::addGlobalScope('branch', function (Builder $builder) {
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

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'entity');
    }

    public function scopePendingCorrection($query)
    {
        return $query->where('correction_status', 'pending');
    }

    public function scopeInProgressCorrection($query)
    {
        return $query->where('correction_status', 'in_progress');
    }

    public function scopeCompletedCorrection($query)
    {
        return $query->where('correction_status', 'completed');
    }

    public function scopeVerifiedCorrection($query)
    {
        return $query->where('correction_status', 'verified');
    }

    public function scopeWaivedCorrection($query)
    {
        return $query->where('correction_status', 'waived');
    }

    public function scopeByQuestion($query, $questionId)
    {
        return $query->where('question_id', $questionId);
    }

    public function getCorrectionStatusText()
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

    public function isOverdue()
    {
        if (!$this->corrective_deadline) {
            return false;
        }
        
        return $this->corrective_deadline < now()->toDateString() 
            && !in_array($this->correction_status, ['completed', 'verified', 'waived']);
    }

    public function calculateScore()
    {
        if (!$this->question) {
            return $this;
        }
        
        if ($this->question->type === 'yes_no') {
            $this->score = $this->answer_value === 'نعم' ? $this->question->max_score : 0;
            $this->save();
        }
        
        return $this;
    }
}
