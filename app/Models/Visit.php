<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Illuminate\Database\Eloquent\Builder;

class Visit extends Model
{
    use HasFactory, SoftDeletes, HasRelationships;

    protected $fillable = [];

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

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeTomorrowVisits($query)
    {
        return $query
            //->scheduled()
            //->whereDate('scheduled_date', now()->addDay()->toDateString())
            ->select('auditor_id', \DB::raw('count(*) as total_visits'))
            ->groupBy('auditor_id');

    }

}

