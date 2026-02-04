<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checklist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'version',
        'company_id',
        'industry_type',
        'total_score',
        'passing_score',
        'time_estimate',
        'is_active',
        'is_template',
        'created_by',
    ];

    protected $casts = [
        'total_score' => 'integer',
        'passing_score' => 'integer',
        'time_estimate' => 'integer',
        'is_active' => 'boolean',
        'is_template' => 'boolean',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeGeneral($query)
    {
        return $query->whereNull('company_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhereNull('company_id');
        });
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    // Methods
    public function getQuestionsCount()
    {
        return $this->questions()->count();
    }

    public function getActiveQuestionsCount()
    {
        return $this->questions()->where('is_active', true)->count();
    }

    public function getTotalMaxScore()
    {
        return $this->questions()->sum('max_score');
    }

    public function isPassingScore($score)
    {
        return $score >= $this->passing_score;
    }

    public function duplicate($newName, $newCode = null)
    {
        $newChecklist = $this->replicate();
        $newChecklist->name = $newName;
        $newChecklist->code = $newCode;
        $newChecklist->created_by = auth()->id();
        $newChecklist->save();

        // Duplicate questions
        foreach ($this->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->checklist_id = $newChecklist->id;
            $newQuestion->save();
        }

        return $newChecklist;
    }
}
