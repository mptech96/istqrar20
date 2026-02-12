<?php

namespace App\Services\V1\Statistics;

use App\Models\V1\Statistic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncEnterpriseStatisticsEngine
{
    /**
     * تحديث الإحصائيات بناءً على الزيارة
     *
     * @param mixed $visit
     * @return void
     */
    public function updateFromVisit($visit)
    {
        try {
            DB::transaction(function () use ($visit) {
                $this->incrementTotalVisits($visit);
                $this->updateAverageScore($visit);
                $this->updateCompletionRate($visit);
                // $this->refreshCache($visit);
            });
        } catch (\Exception $e) {
            Log::error('Error updating statistics from visit', [
                'visit_id' => $visit->id ?? null,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * زيادة عداد الزيارات
     *
     * @param mixed $visit
     * @return void
     */
    private function incrementTotalVisits($visit)
    {
        $companyId = $visit->company->id;
        
        $this->atomicUpsert(
            'total_visits',
            $visit->visit_date ?? now()->toDateString(),
            1,
            $companyId,
            $visit->branch_id,
            $visit->auditor_id ?? null
        );
    }

    /**
     * تحديث متوسط الدرجات
     *
     * @param mixed $visit
     * @return void
     */
    private function updateAverageScore($visit)
    {
        $companyId = $visit->company->id;
        $recordDate = $visit->visit_date ?? now()->toDateString();
        
        // الحصول على عدد الزيارات الكلي لهذا التاريخ
        $totalVisits = Statistic::where('record_date', $recordDate)
            ->where('metric_name', 'total_visits')
            ->where('period_type', 'daily')
            ->where('company_id', $companyId)
            ->where('branch_id', $visit->branch_id)
            ->value('metric_value') ?? 0;

        // إذا كان هذا أول زيارة، نبدأ بالعد من 1
        $totalVisits = max($totalVisits, 1);

        // البحث عن سجل متوسط الدرجات أو إنشاؤه
        $stat = Statistic::firstOrNew([
            'record_date' => $recordDate,
            'metric_name' => 'average_score',
            'period_type' => 'daily',
            'company_id' => $companyId,
            'branch_id' => $visit->branch_id,
        ]);

        // التأكد من وجود قيمة سابقة
        $currentAverage = $stat->metric_value ?? 0;
        $visitPercentage = $visit->percentage ?? 0;

        // حساب المتوسط الجديد
        if ($stat->exists) {
            // تحديث المتوسط الموجود
            $newAverage = (($currentAverage * ($totalVisits - 1)) + $visitPercentage) / $totalVisits;
        } else {
            // إنشاء سجل جديد
            $newAverage = $visitPercentage;
        }

        // حفظ أو تحديث السجل
        $stat->metric_value = round($newAverage, 2);
        $stat->calculated_at = now();
        
        // تعيين القيم الأخرى إذا كان سجلاً جديداً
        if (!$stat->exists) {
            $stat->record_date = $recordDate;
            $stat->metric_name = 'average_score';
            $stat->period_type = 'daily';
            $stat->company_id = $companyId;
            $stat->branch_id = $visit->branch_id;
            $stat->auditor_id = $visit->auditor_id ?? null;
        }

        $stat->save();

        // تحديث المتوسط الشهري أيضاً
        $this->updateMonthlyAverage($visit);
    }

    /**
     * تحديث متوسط الدرجات الشهري
     *
     * @param mixed $visit
     * @return void
     */
    private function updateMonthlyAverage($visit)
    {
        $companyId = $visit->company->id;
        $firstDayOfMonth = now()->startOfMonth()->toDateString();
        
        // حساب متوسط الشهر الحالي للفرع
        $monthlyAverage = DB::table('visits')
            ->where('branch_id', $visit->branch_id)
            ->where('status', 'completed')
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->avg('percentage');

        $monthlyAverage = $monthlyAverage ?? 0;

        $this->atomicUpsert(
            'average_score',
            $firstDayOfMonth,
            round($monthlyAverage, 2),
            $companyId,
            $visit->branch_id,
            null,
            'monthly'
        );
    }

    /**
     * تحديث نسبة الإنجاز
     *
     * @param mixed $visit
     * @return void
     */
    private function updateCompletionRate($visit)
    {
        $companyId = $visit->company->id;
        
        $totalVisits = Statistic::where('metric_name', 'total_visits')
            ->where('company_id', $companyId)
            ->where('branch_id', $visit->branch_id)
            ->sum('metric_value');

        $totalVisits = max($totalVisits, 1);

        $completedVisits = DB::table('visits')
            ->where('branch_id', $visit->branch_id)
            ->where('status', 'completed')
            ->count();

        $completionRate = ($completedVisits / $totalVisits) * 100;

        $this->atomicUpsert(
            'completion_rate',
            now()->toDateString(),
            round($completionRate, 2),
            $companyId,
            $visit->branch_id,
            null,
            'daily'
        );
    }

    /**
     * إدراج أو تحديث إحصائية بشكل ذري
     *
     * @param string $metric
     * @param string $date
     * @param float $value
     * @param int|null $companyId
     * @param int|null $branchId
     * @param int|null $auditorId
     * @param string $periodType
     * @return void
     */
    private function atomicUpsert(
        string $metric,
        string $date,
        float $value,
        $companyId = null,
        $branchId = null,
        $auditorId = null,
        string $periodType = 'daily'
    ) {
        // التحقق من وجود السجل
        $existing = Statistic::where('record_date', $date)
            ->where('metric_name', $metric)
            ->where('period_type', $periodType)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($auditorId, fn($q) => $q->where('auditor_id', $auditorId))
            ->first();

        $previousValue = $existing->metric_value ?? $this->getPreviousValue($metric, $date, $periodType, $companyId, $branchId, $auditorId);
        
        if ($metric === 'total_visits' && $existing) {
            $value = $existing->metric_value + 1;
        }
        
        $percentageChange = $this->calculatePercentageChange($value, $previousValue);
        $trendDirection = $this->determineTrendDirection($value, $previousValue);

        DB::table('statistics')->updateOrInsert(
            [
                'record_date' => $date,
                'metric_name' => $metric,
                'period_type' => $periodType,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'auditor_id' => $auditorId,
            ],
            [
                'metric_value' => $value,
                'previous_value' => $previousValue,
                'percentage_change' => $percentageChange,
                'trend_direction' => $trendDirection,
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * الحصول على القيمة السابقة
     *
     * @param string $metric
     * @param string $date
     * @param string $periodType
     * @param int|null $companyId
     * @param int|null $branchId
     * @param int|null $auditorId
     * @return float|null
     */
    private function getPreviousValue(
        string $metric,
        string $date,
        string $periodType,
        $companyId = null,
        $branchId = null,
        $auditorId = null
    ): ?float {
        try {
            $previousDate = match($periodType) {
                'daily' => now()->parse($date)->subDay()->toDateString(),
                'weekly' => now()->parse($date)->subWeek()->toDateString(),
                'monthly' => now()->parse($date)->subMonth()->toDateString(),
                'quarterly' => now()->parse($date)->subQuarter()->toDateString(),
                'yearly' => now()->parse($date)->subYear()->toDateString(),
                default => now()->parse($date)->subDay()->toDateString()
            };

            $previous = Statistic::where('metric_name', $metric)
                ->where('record_date', $previousDate)
                ->where('period_type', $periodType)
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($auditorId, fn($q) => $q->where('auditor_id', $auditorId))
                ->first();

            return $previous?->metric_value;
        } catch (\Exception $e) {
            Log::warning('Error getting previous value', [
                'metric' => $metric,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * حساب نسبة التغيير
     *
     * @param float $current
     * @param float|null $previous
     * @return float|null
     */
    private function calculatePercentageChange(float $current, ?float $previous): ?float
    {
        if (is_null($previous) || $previous == 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * تحديد اتجاه التغيير
     *
     * @param float $current
     * @param float|null $previous
     * @return string
     */
    private function determineTrendDirection(float $current, ?float $previous): string
    {
        if (is_null($previous)) {
            return 'stable';
        }

        if ($current > $previous) {
            return 'up';
        } elseif ($current < $previous) {
            return 'down';
        }

        return 'stable';
    }

    /**
     * تحديث إحصائيات متعددة دفعة واحدة
     *
     * @param array $visits
     * @return void
     */
    public function bulkUpdateFromVisits(array $visits)
    {
        DB::transaction(function () use ($visits) {
            foreach ($visits as $visit) {
                $this->incrementTotalVisits($visit);
                $this->updateAverageScore($visit);
                $this->updateCompletionRate($visit);
            }
        });
    }

    /**
     * تحديث الكاش
     *
     * @param mixed $visit
     * @return void
     */
    private function refreshCache($visit)
    {
        $companyId = $visit->company->id;
        
        if ($visit->branch_id) {
            Cache::forget("dashboard:branch:{$visit->branch_id}");
            Cache::forget("statistics:branch:{$visit->branch_id}:daily");
            Cache::forget("statistics:branch:{$visit->branch_id}:monthly");
        }
        
        if ($companyId) {
            Cache::forget("dashboard:company:{$companyId}");
            Cache::forget("statistics:company:{$companyId}:daily");
            Cache::forget("statistics:company:{$companyId}:monthly");
        }
    }
}