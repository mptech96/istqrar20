<?php

namespace App\Services\V1\Analysis;

use App\Models\V1\Visit;
use App\Models\V1\VisitAnalysis;
use Illuminate\Support\Facades\DB;

class VisitAnalysisService
{
    public function generate(Visit $visit): VisitAnalysis
    {
        $answers = $visit->answers()->with('question.criteria')->get();

        $totalQuestions = $visit->checklist->questions()->count();
        $answeredQuestions = $answers->count();

        $criticalErrors = $answers->filter(fn($a) => $a->question->is_critical && $a->score < ($a->question->passing_score ?? 1))->count();

        $totalScore = $answers->sum('score');
        $potentialScore = $answers->sum('max_score');
        $lostPoints = $potentialScore - $totalScore;
        $percentage = $potentialScore > 0 ? ($totalScore / $potentialScore) * 100 : 0;

        $category = $this->determineHybridCategory($visit, $answers);

        $swot = $this->computeSWOTAndRecommendations($answers);

        return VisitAnalysis::updateOrCreate(
            ['visit_id' => $visit->id],
            [
                'branch_id' => $visit->branch_id,
                'company_id' => $visit->branch->company_id,
                'analysis_type' => 'automatic',
                'analysis_date' => now(),
                'total_questions' => $totalQuestions,
                'answered_questions' => $answeredQuestions,
                'critical_errors' => $criticalErrors,
                'total_score' => $totalScore,
                'lost_points' => $lostPoints,
                'potential_score' => $potentialScore,
                'summary' => $this->computeSummary($percentage, $criticalErrors),
                'strengths' => $swot['strengths'],
                'weaknesses' => $swot['weaknesses'],
                'opportunities' => $swot['opportunities'],
                'threats' => $swot['threats'],
                'recommendations' => $swot['recommendations'],
                'category' => $category,
                'status' => 'generated',
            ]
        );
    }

    private function determineHybridCategory(Visit $visit, $answers): string
    {
        $hasCritical = $answers->contains(fn($a) => $a->question->is_critical && $a->score < ($a->question->passing_score ?? 1));
        if ($hasCritical) return 'Critical';

        $grouped = $answers->filter(fn($a) => $a->question && $a->question->criteria)
                           ->groupBy(fn($a) => $a->question->criteria->id);

        $weightedScore = 0;
        $totalWeight = 0;

        foreach ($grouped as $criteriaAnswers) {
            $criteria = $criteriaAnswers->first()->question->criteria;
            $criteriaScore = $criteriaAnswers->sum('score');
            $criteriaMax = $criteriaAnswers->sum('max_score');
            if ($criteriaMax <= 0) continue;

            $percentage = ($criteriaScore / $criteriaMax) * 100;
            $weightedScore += $percentage * $criteria->weight;
            $totalWeight += $criteria->weight;
        }

        if ($totalWeight == 0) return 'Unclassified';

        $finalPercentage = $weightedScore / $totalWeight;

        // جلب النطاق المناسب ديناميكياً من قاعدة البيانات
        $range = DB::table('criteria_ranges')
            ->where('min_score', '<=', $finalPercentage)
            ->where('max_score', '>=', $finalPercentage)
            ->orderBy('min_score', 'asc')
            ->first();

        return $range ? $range->name : 'Unclassified';
    }

    private function computeSWOTAndRecommendations($answers)
    {
        $strengths = [];
        $weaknesses = [];
        $opportunities = [];
        $threats = [];
        $recommendations = [];

        $grouped = $answers->filter(fn($a) => $a->question && $a->question->criteria)
                           ->groupBy(fn($a) => $a->question->criteria->id);

        foreach ($grouped as $criteriaAnswers) {
            $criteria = $criteriaAnswers->first()->question->criteria;
            $score = $criteriaAnswers->sum('score');
            $max = $criteriaAnswers->sum('max_score');
            $percentage = $max > 0 ? ($score / $max) * 100 : 0;

            // جلب النطاق المناسب ديناميكياً لكل معيار
            $range = DB::table('criteria_ranges')
                ->where('criteria_id', $criteria->id)
                ->where('min_score', '<=', $percentage)
                ->where('max_score', '>=', $percentage)
                ->orderBy('min_score', 'asc')
                ->first();

            $rangeName = $range ? $range->name : null;

            if ($rangeName) {
                // تحديد Strength / Weakness / Opportunity حسب ترتيب النطاق
                if ($percentage >= $range->min_score && $percentage == $range->max_score) {
                    $strengths[] = $criteria->name;
                } elseif ($percentage < $range->min_score + (($range->max_score - $range->min_score) * 0.5)) {
                    $weaknesses[] = $criteria->name;
                } else {
                    $opportunities[] = $criteria->name;
                }
            }

            // التهديدات: أي معيار حرِج أو <50%
            if ($criteriaAnswers->contains(fn($a) => $a->question->is_critical && $a->score < ($a->question->passing_score ?? 1)) || $percentage < 50) {
                $threats[] = $criteria->name;
            }

            // توصيات ذكية
            if ($percentage < 70) {
                $recommendations[] = [
                    'priority' => 'medium',
                    'action' => "تحسين الالتزام بمعيار {$criteria->name}",
                    'deadline' => now()->addDays(30)->toDateString(),
                ];
            }

            if ($criteriaAnswers->contains(fn($a) => $a->question->is_critical && $a->score < ($a->question->passing_score ?? 1))) {
                $recommendations[] = [
                    'priority' => 'high',
                    'action' => "معالجة الأخطاء الحرجة في معيار {$criteria->name}",
                    'deadline' => now()->addDays(7)->toDateString(),
                ];
            }
        }

        return [
            'strengths' => !empty($strengths) ? "نقاط القوة في: " . implode(', ', $strengths) . "." : "لا توجد نقاط قوة واضحة.",
            'weaknesses' => !empty($weaknesses) ? "نقاط الضعف في: " . implode(', ', $weaknesses) . "." : "لا توجد نقاط ضعف رئيسية.",
            'opportunities' => !empty($opportunities) ? "فرص التحسين في: " . implode(', ', $opportunities) . "." : null,
            'threats' => !empty($threats) ? "تهديدات: " . implode(', ', $threats) . "." : null,
            'recommendations' => $recommendations,
        ];
    }

    private function computeSummary(float $percentage, int $criticalErrors): string
    {
        $text = "تم تنفيذ الزيارة بنسبة " . round($percentage, 2) . "%";
        $text .= $criticalErrors > 0 ? " مع وجود {$criticalErrors} أخطاء حرجة." : " دون أخطاء حرجة.";
        return $text;
    }
}