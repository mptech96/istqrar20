<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'checklist_id',
        'criteria_id',
        'question_text',
        'description',
        'help_text',
        'type',
        'options',
        'correct_answer',
        'weight',
        'max_score',
        'passing_score',
        'is_required',
        'is_critical',
        'has_attachment',
        'attachment_instructions',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'weight' => 'integer',
        'max_score' => 'integer',
        'passing_score' => 'integer',
        'is_required' => 'boolean',
        'is_critical' => 'boolean',
        'has_attachment' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope('visit', function (Builder $builder) {
            if (auth()->check()){
                
                $user = auth()->user();
                
                if($user->role === 'company_admin') {
                    $builder->whereHas('checklist');
                }
                elseif($user->role === 'branch_manager') {
                    $builder->whereHas('checklist');
                }
                elseif($user->role === 'auditor') {
                    $builder->whereHas('checklist');
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
    
    // Relationships
    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }

    public function criteria()
    {
        return $this->belongsTo(Criteria::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order');
    }

    // Methods
    public function getTypeText()
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

    public function getOptionsArray()
    {
        if ($this->type !== 'multiple_choice' || !$this->options) {
            return [];
        }

        return is_array($this->options) ? $this->options : json_decode($this->options, true);
    }

    public function getOptionByValue($value)
    {
        $options = $this->getOptionsArray();
        
        foreach ($options as $option) {
            if ($option['value'] === $value) {
                return $option;
            }
        }
        
        return null;
    }

    public function isAnswerCorrect($answer)
    {
        if (!$this->correct_answer) {
            return null;
        }

        return $answer === $this->correct_answer;
    }

    public function calculateScore($answer)
    {
        // This is a simplified version - actual implementation would depend on question type
        if ($this->type === 'multiple_choice') {
            $option = $this->getOptionByValue($answer);
            return $option ? $option['score'] : 0;
        }

        // For other types, you might need more complex logic
        return 0;
    }
}
