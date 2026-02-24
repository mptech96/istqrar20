<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AiSuggestion extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * اسم الجدول في قاعدة البيانات
     *
     * @var string
     */
    protected $table = 'ai_suggestions';

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = [
        // الربط مع الإحصائيات
        'statistic_id',
        'metric_name',
        'record_date',
        'period_type',

        // نطاق التأثير
        'company_id',
        'branch_id',

        // حالة التقييم
        'current_value',
        'previous_value',
        'percentage_change',
        'trend_direction',

        // تصنيف الذكاء الاصطناعي
        'severity',
        'status',

        // مخرجات الذكاء الاصطناعي
        'suggestion',
        'summary',
        'confidence',

        // بيانات النموذج
        'ai_provider',
        'ai_model',
        'tokens_used',
        'processing_time',
        'metadata',

        // متابعة التنفيذ
        'is_applied',
        'applied_by',
        'applied_at',

        // ملاحظات إدارية
        'admin_notes',
    ];

    /**
     * تحويل الحقول إلى أنواع بيانات
     *
     * @var array
     */
    protected $casts = [
        'record_date' => 'date',
        'current_value' => 'decimal:2',
        'previous_value' => 'decimal:2',
        'percentage_change' => 'decimal:2',
        'confidence' => 'decimal:2',
        'tokens_used' => 'integer',
        'processing_time' => 'integer',
        'is_applied' => 'boolean',
        'applied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'metadata' => 'array', // تحويل حقل JSON إلى مصفوفة
    ];

    /**
     * القيم الافتراضية للحقول
     *
     * @var array
     */
    protected $attributes = [
        'is_applied' => false,
        'severity' => 'info',
        'status' => 'average',
        'trend_direction' => 'stable',
        'period_type' => 'daily',
        'confidence' => 0.00,
    ];

    protected static function booted()
    {
        static::addGlobalScope('branch', function (Builder $builder) {
            if (auth()->check()){
                
                $user = auth()->user();
                
                if($user->role === 'company_admin') {
                    $builder->whereHas('company');
                }
                elseif($user->role === 'branch_manager') {
                    $builder->whereHas('branch');
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

    /**
     * -------------------------------
     * العلاقات
     * -------------------------------
     */

    /**
     * العلاقة مع الإحصائيات
     *
     * @return BelongsTo
     */
    public function statistic(): BelongsTo
    {
        return $this->belongsTo(Statistic::class, 'statistic_id');
    }

    /**
     * العلاقة مع الشركة
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * العلاقة مع الفرع
     *
     * @return BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * العلاقة مع المستخدم الذي طبق الاقتراح
     *
     * @return BelongsTo
     */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * العلاقة متعددة الأشكال مع المرفقات
     *
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * العلاقة مع التعليقات
     *
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * -------------------------------
     * النطاقات (Scopes)
     * -------------------------------
     */

    /**
     * نطاق للاقتراحات غير المطبقة
     *
     * @param $query
     * @return mixed
     */
    public function scopeNotApplied($query)
    {
        return $query->where('is_applied', false);
    }

    /**
     * نطاق للاقتراحات المطبقة
     *
     * @param $query
     * @return mixed
     */
    public function scopeApplied($query)
    {
        return $query->where('is_applied', true);
    }

    /**
     * نطاق للاقتراحات حسب مستوى الخطورة
     *
     * @param $query
     * @param string $severity
     * @return mixed
     */
    public function scopeOfSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * نطاق للاقتراحات حسب الحالة
     *
     * @param $query
     * @param string $status
     * @return mixed
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * نطاق للاقتراحات حسب اتجاه المؤشر
     *
     * @param $query
     * @param string $direction
     * @return mixed
     */
    public function scopeTrendDirection($query, string $direction)
    {
        return $query->where('trend_direction', $direction);
    }

    /**
     * نطاق للاقتراحات حسب نوع الفترة
     *
     * @param $query
     * @param string $periodType
     * @return mixed
     */
    public function scopeOfPeriodType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * نطاق للاقتراحات حسب الشركة
     *
     * @param $query
     * @param int $companyId
     * @return mixed
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * نطاق للاقتراحات حسب الفرع
     *
     * @param $query
     * @param int $branchId
     * @return mixed
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * نطاق للاقتراحات حسب الفترة الزمنية
     *
     * @param $query
     * @param string $startDate
     * @param string $endDate
     * @return mixed
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('record_date', [$startDate, $endDate]);
    }

    /**
     * نطاق للاقتراحات ذات الثقة العالية
     *
     * @param $query
     * @param float $minConfidence
     * @return mixed
     */
    public function scopeHighConfidence($query, float $minConfidence = 80)
    {
        return $query->where('confidence', '>=', $minConfidence);
    }

    /**
     * نطاق للاقتراحات الحرجة
     *
     * @param $query
     * @return mixed
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical')
            ->orWhere('status', 'critical');
    }

    /**
     * -------------------------------
     * وظائف مساعدة
     * -------------------------------
     */

    /**
     * تطبيق الاقتراح
     *
     * @param int $userId
     * @param array $notes
     * @return bool
     */
    public function apply(int $userId, array $notes = []): bool
    {
        $this->is_applied = true;
        $this->applied_by = $userId;
        $this->applied_at = now();
        
        if (!empty($notes)) {
            $this->admin_notes = json_encode($notes, JSON_UNESCAPED_UNICODE);
        }
        
        return $this->save();
    }

    /**
     * إلغاء تطبيق الاقتراح
     *
     * @return bool
     */
    public function unapply(): bool
    {
        $this->is_applied = false;
        $this->applied_by = null;
        $this->applied_at = null;
        
        return $this->save();
    }

    /**
     * الحصول على لون مستوى الخطورة
     *
     * @return string
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'secondary',
        };
    }

    /**
     * الحصول على نص مستوى الخطورة
     *
     * @return string
     */
    public function getSeverityTextAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'حرج',
            'warning' => 'تحذير',
            'info' => 'معلوماتي',
            default => 'غير محدد',
        };
    }

    /**
     * الحصول على نص الحالة
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'excellent' => 'ممتاز',
            'good' => 'جيد',
            'average' => 'متوسط',
            'poor' => 'ضعيف',
            'critical' => 'حرج',
            default => 'غير محدد',
        };
    }

    /**
     * الحصول على لون الحالة
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'excellent' => 'success',
            'good' => 'primary',
            'average' => 'info',
            'poor' => 'warning',
            'critical' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * الحصول على نص اتجاه المؤشر
     *
     * @return string
     */
    public function getTrendTextAttribute(): string
    {
        return match($this->trend_direction) {
            'up' => 'صاعد 📈',
            'down' => 'هابط 📉',
            'stable' => 'مستقر ➡️',
            default => 'غير محدد',
        };
    }

    /**
     * الحصول على أيقونة اتجاه المؤشر
     *
     * @return string
     */
    public function getTrendIconAttribute(): string
    {
        return match($this->trend_direction) {
            'up' => 'arrow-up',
            'down' => 'arrow-down',
            'stable' => 'minus',
            default => 'question',
        };
    }

    /**
     * الحصول على نسبة الثقة كنص منسق
     *
     * @return string
     */
    public function getConfidenceTextAttribute(): string
    {
        return $this->confidence ? number_format($this->confidence, 1) . '%' : 'غير محدد';
    }

    /**
     * الحصول على التكلفة التقديرية
     *
     * @return float
     */
    public function getEstimatedCostAttribute(): float
    {
        // تقدير التكلفة: $0.03 لكل 1000 token
        return ($this->tokens_used / 1000) * 0.03;
    }

    /**
     * التحقق مما إذا كان الاقتراح قد طبق
     *
     * @return bool
     */
    public function getIsAppliedAttribute($value): bool
    {
        return (bool) $value;
    }

    /**
     * الحصول على وقت المعالجة بالثواني
     *
     * @return float
     */
    public function getProcessingTimeSecondsAttribute(): float
    {
        return $this->processing_time ? round($this->processing_time / 1000, 2) : 0;
    }

    /**
     * الحصول على التحليل الكامل من حقل metadata
     *
     * @return array
     */
    public function getFullAnalysisAttribute(): array
    {
        return $this->metadata['analyses'] ?? [];
    }

    /**
     * الحصول على التوصيات من التحليل
     *
     * @return array
     */
    public function getRecommendationsAttribute(): array
    {
        return $this->metadata['analyses']['recommendations'] ?? [];
    }

    /**
     * الحصول على خطة العمل
     *
     * @return array
     */
    public function getActionPlanAttribute(): array
    {
        return $this->metadata['analyses']['action_plan'] ?? [];
    }

    /**
     * الحصول على المخاطر
     *
     * @return array
     */
    public function getRisksAttribute(): array
    {
        return $this->metadata['analyses']['risk_assessment'] ?? [];
    }

    /**
     * الحصول على التوصيات العاجلة
     *
     * @return array
     */
    public function getImmediateActionsAttribute(): array
    {
        return $this->recommendations['immediate_actions'] ?? [];
    }

    /**
     * الحصول على الفرص السريعة
     *
     * @return array
     */
    public function getQuickWinsAttribute(): array
    {
        return $this->recommendations['quick_wins'] ?? [];
    }

    /**
     * -------------------------------
     * وظائف إحصائية
     * -------------------------------
     */

    /**
     * الحصول على إحصائيات الاقتراحات
     *
     * @param int|null $companyId
     * @param int|null $branchId
     * @return array
     */
    public static function getStats(?int $companyId = null, ?int $branchId = null): array
    {
        $query = self::query();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $total = $query->count();
        $applied = (clone $query)->where('is_applied', true)->count();
        $notApplied = $total - $applied;

        $critical = (clone $query)->where('severity', 'critical')->count();
        $warning = (clone $query)->where('severity', 'warning')->count();
        $info = (clone $query)->where('severity', 'info')->count();

        $byStatus = [
            'excellent' => (clone $query)->where('status', 'excellent')->count(),
            'good' => (clone $query)->where('status', 'good')->count(),
            'average' => (clone $query)->where('status', 'average')->count(),
            'poor' => (clone $query)->where('status', 'poor')->count(),
            'critical' => (clone $query)->where('status', 'critical')->count(),
        ];

        $avgConfidence = (clone $query)->avg('confidence');
        $totalTokens = (clone $query)->sum('tokens_used');
        $avgProcessingTime = (clone $query)->avg('processing_time');

        $totalCost = 0;
        foreach ((clone $query)->get() as $suggestion) {
            $totalCost += $suggestion->estimated_cost;
        }

        return [
            'total' => $total,
            'applied' => $applied,
            'applied_percentage' => $total > 0 ? round(($applied / $total) * 100, 2) : 0,
            'not_applied' => $notApplied,
            'by_severity' => [
                'critical' => $critical,
                'warning' => $warning,
                'info' => $info,
            ],
            'by_status' => $byStatus,
            'average_confidence' => round($avgConfidence ?? 0, 2),
            'total_tokens' => $totalTokens,
            'average_processing_time_ms' => round($avgProcessingTime ?? 0, 2),
            'total_estimated_cost_usd' => round($totalCost, 4),
        ];
    }

    /**
     * الحصول على الاقتراحات الشائعة حسب المقياس
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getTopMetrics(int $limit = 10)
    {
        return self::select('metric_name')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(confidence) as avg_confidence')
            ->selectRaw('SUM(is_applied) as applied_count')
            ->groupBy('metric_name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * -------------------------------
     * Boot functions
     * -------------------------------
     */

    /**
     * دالة boot للنموذج
     */
    protected static function boot()
    {
        parent::boot();

        // قبل الإنشاء
        static::creating(function ($suggestion) {
            // حساب نسبة التغيير إذا توفرت القيم
            if ($suggestion->previous_value && $suggestion->current_value) {
                $change = $suggestion->current_value - $suggestion->previous_value;
                $suggestion->percentage_change = $suggestion->previous_value != 0 
                    ? round(($change / $suggestion->previous_value) * 100, 2)
                    : 0;
            }
        });

        // بعد الإنشاء
        static::created(function ($suggestion) {
            // تسجيل إنشاء الاقتراح
            activity()
                ->performedOn($suggestion)
                ->causedBy(auth()->user())
                ->log('تم إنشاء اقتراح ذكاء اصطناعي جديد');
        });

        // بعد التحديث
        static::updated(function ($suggestion) {
            if ($suggestion->wasChanged('is_applied') && $suggestion->is_applied) {
                // تسجيل تطبيق الاقتراح
                activity()
                    ->performedOn($suggestion)
                    ->causedBy(auth()->user())
                    ->log('تم تطبيق اقتراح الذكاء الاصطناعي');
            }
        });
    }
}
