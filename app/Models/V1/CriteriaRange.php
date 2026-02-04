<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CriteriaRange extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'criteria_id',
        'name',
        'min_score',
        'max_score',
        'color',
        'description',
    ];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
    ];

    // Relationships
    public function criteria()
    {
        return $this->belongsTo(Criteria::class);
    }

    // Scopes
    public function scopeByScore($query, $score)
    {
        return $query->where('min_score', '<=', $score)
                    ->where('max_score', '>=', $score);
    }

    // Methods
    public function getScoreRangeAttribute()
    {
        return "{$this->min_score} - {$this->max_score}";
    }

    public function getMidpoint()
    {
        return ($this->min_score + $this->max_score) / 2;
    }

    public function isInRange($score)
    {
        return $score >= $this->min_score && $score <= $this->max_score;
    }
}
