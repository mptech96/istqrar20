<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Criteria extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'category',
        'weight',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];
    
    protected static function booted()
    {
        static::addGlobalScope('visit', function (Builder $builder) {
            if (auth()->check()){
                
                $user = auth()->user();
                
                if($user->role === 'company_admin') {
                    $builder->whereRaw('1 = 1');
                }
                elseif($user->role === 'branch_manager') {
                    $builder->whereRaw('1 = 1');
                }
                elseif($user->role === 'auditor') {
                    $builder->whereRaw('1 = 1');
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
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ranges()
    {
        return $this->hasMany(CriteriaRange::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Methods
    public function getRangeForScore($score)
    {
        return $this->ranges()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
    }

    public function getWeightedScore($score)
    {
        return ($score / 100) * $this->weight;
    }

    public function hasCompleteCoverage()
    {
        $ranges = $this->ranges()->orderBy('min_score')->get();
        
        if ($ranges->isEmpty()) {
            return false;
        }
        
        $firstRange = $ranges->first();
        $lastRange = $ranges->last();
        
        return $firstRange->min_score === 0 && $lastRange->max_score === 100;
    }
}
