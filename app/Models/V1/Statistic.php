<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Statistic extends Model
{
    use HasFactory, HasRelationships;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'statistics';
    
    public $timestamps = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'record_date',
        'metric_name',
        'metric_value',
        'period_type',
        'company_id',
        'branch_id',
        'auditor_id',
        'additional_data',
        'trend_direction',
        'previous_value',
        'percentage_change',
        'calculated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'record_date'       => 'date',
        'metric_value'      => 'decimal:2',
        'additional_data'   => 'json',
        'previous_value'    => 'decimal:2',
        'percentage_change' => 'decimal:2',
        'calculated_at'     => 'datetime',
        'period_type'       => 'string', // enum handled in validation
        'trend_direction'   => 'string',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the company that this statistic belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that this statistic belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the auditor (user) that this statistic belongs to.
     */
    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    /**
     * Get all AI suggestions generated for this statistic.
     */
    public function aiSuggestions()
    {
        return $this->hasMany(AiSuggestion::class, 'statistic_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include statistics for a specific metric.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $metricName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForMetric($query, $metricName)
    {
        return $query->where('metric_name', $metricName);
    }

    /**
     * Scope a query to only include statistics of a given period type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $periodType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfPeriod($query, $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope a query to only include statistics recorded on or after a date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|\Carbon\Carbon  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromDate($query, $date)
    {
        return $query->where('record_date', '>=', $date);
    }

    /**
     * Scope a query to only include statistics recorded on or before a date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|\Carbon\Carbon  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToDate($query, $date)
    {
        return $query->where('record_date', '<=', $date);
    }

    // =========================================================================
    // Accessors & Mutators
    // =========================================================================

    /**
     * Get the metric value formatted with 2 decimal places.
     *
     * @return string
     */
    public function getFormattedMetricValueAttribute()
    {
        return number_format($this->metric_value, 2);
    }

    /**
     * Get the percentage change formatted with 2 decimal places and sign.
     *
     * @return string|null
     */
    public function getFormattedPercentageChangeAttribute()
    {
        if (is_null($this->percentage_change)) {
            return null;
        }
        $sign = $this->percentage_change > 0 ? '+' : '';
        return $sign . number_format($this->percentage_change, 2) . '%';
    }
}
