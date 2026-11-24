<?php

namespace App\Services\V1\Analysis;

use App\Models\V1\Visit;
use App\Models\V1\VisitAnalysis;
use App\Services\V1\Scoring\VisitScoringService;
use Illuminate\Support\Facades\DB;

class VisitAnalysisService
{
    public function generate(Visit $visit): VisitAnalysis
    {
        $scoringService = app(VisitScoringService::class);
        $statistics = $scoringService->calculate($visit);
        $questions = $statistics['questions'];
        $scoringQuestions = $statistics['scoring_questions'];

        $answers = $visit->answers()
            ->with(['question' => fn ($query) => $query->withoutGlobalScopes()->withTrashed()->with('criteria')])
            ->get()
            ->filter(fn ($answer) => $answer->question
                && (int) $answer->question->checklist_id === (int) $visit->checklist_id);
        $answersByQuestion = $answers->keyBy('question_id');

        $criticalErrors = $scoringQuestions->filter(function ($question) use ($answersByQuestion, $scoringService) {
            if (!$question->is_critical) {
                return false;
            }

            $answer = $answersByQuestion->get($question->id);
            $answerMaximum = $answer && $answer->max_score !== null
                ? (float) $answer->max_score
                : (float) $scoringService->maximumAchievableScore($question);
            $score = $answer ? min($answerMaximum, max(0, (float) $answer->score)) : 0;

            return $score < (float) ($question->passing_score ?? 1);
        })->count();

        $totalScore = (float) $statistics['total_score'];
        $potentialScore = (float) $statistics['max_possible_score'];
        $lostPoints = max(0, $potentialScore - $totalScore);
        $percentage = (float) $statistics['percentage'];
        $swot = $this->computeSWOTAndRecommendations($questions, $answersByQuestion, $scoringService);

        return VisitAnalysis::updateOrCreate(
            ['visit_id' => $visit->id],
            [
                'branch_id' => $visit->branch_id,
                'company_id' => $visit->branch->company_id,
                'analysis_type' => 'automatic',
                'analysis_date' => now(),
                'total_questions' => $statistics['total_questions'],
                'answered_questions' => $statistics['answered_questions'],
                'correct_answers' => $answers->filter(fn ($answer) => $answer->is_correct === true)->count(),
                'incorrect_answers' => $answers->filter(fn ($answer) => $answer->is_correct === false)->count(),
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
                'status' => 'generated',
            ]
        );
    }

    private function computeSWOTAndRecommendations($questions, $answersByQuestion, VisitScoringService $scoringService): array
    {
        $strengths = [];
        $weaknesses = [];
        $opportunities = [];
        $threats = [];
        $recommendations = [];

        $grouped = $questions
            ->filter(fn ($question) => $question->criteria)
            ->groupBy(fn ($question) => $question->criteria->id);

        foreach ($grouped as $criteriaQuestions) {
            $criteria = $criteriaQuestions->first()->criteria;
            $scoringQuestions = $criteriaQuestions
                ->reject(fn ($question) => in_array($question->type, ['text', 'date', 'file'], true));
            $maximumScore = (float) $scoringQuestions
                ->sum(fn ($question) => $scoringService->maximumAchievableScore($question));

            if ($maximumScore <= 0) {
                continue;
            }

            $earnedScore = (float) $scoringQuestions->sum(function ($question) use ($answersByQuestion, $scoringService) {
                $answer = $answersByQuestion->get($question->id);
                if (!$answer) {
                    return 0;
                }

                $answerMaximum = $answer->max_score !== null
                    ? (float) $answer->max_score
                    : (float) $scoringService->maximumAchievableScore($question);

                return min($answerMaximum, max(0, (float) $answer->score));
            });

            $percentage = round(($earnedScore / $maximumScore) * 100, 2);
            $range = DB::table('criteria_ranges')
                ->where('criteria_id', $criteria->id)
                ->where('min_score', '<=', $percentage)
                ->where('max_score', '>=', $percentage)
                ->whereNull('deleted_at')
                ->orderBy('min_score', 'desc')
                ->first();

            $rangeName = $range ? $range->name : null;
            if ($rangeName) {
                $normalizedRangeName = strtolower((string) $rangeName);
                $isStrengthRange = preg_match('/ممتاز|جيد|قوي|excellent|good|strong/', $normalizedRangeName) === 1;
                $isWeaknessRange = preg_match('/ضعيف|سيئ|متدن|poor|weak|critical/', $normalizedRangeName) === 1;

                if ($isStrengthRange || (!$isWeaknessRange && $percentage >= 80)) {
                    $strengths[] = $criteria->name;
                } elseif ($isWeaknessRange || $percentage < 50) {
                    $weaknesses[] = $criteria->name;
                } else {
                    $opportunities[] = $criteria->name;
                }
            }

            $hasCriticalError = $criteriaQuestions
                ->reject(fn ($question) => in_array($question->type, ['text', 'date', 'file'], true))
                ->contains(function ($question) use ($answersByQuestion, $scoringService) {
                if (!$question->is_critical) {
                    return false;
                }

                $answer = $answersByQuestion->get($question->id);
                $answerMaximum = $answer && $answer->max_score !== null
                    ? (float) $answer->max_score
                    : (float) $scoringService->maximumAchievableScore($question);
                $score = $answer ? min($answerMaximum, max(0, (float) $answer->score)) : 0;

                return $score < (float) ($question->passing_score ?? 1);
            });

            if ($hasCriticalError || $percentage < 50) {
                $threats[] = $criteria->name;
            }

            if ($percentage < 70) {
                $recommendations[] = [
                    'priority' => 'medium',
                    'action' => "تحسين الالتزام بمعيار {$criteria->name}",
                    'deadline' => now()->addDays(30)->toDateString(),
                ];
            }

            if ($hasCriticalError) {
                $recommendations[] = [
                    'priority' => 'high',
                    'action' => "معالجة الأخطاء الحرجة في معيار {$criteria->name}",
                    'deadline' => now()->addDays(7)->toDateString(),
                ];
            }
        }

        return [
            'strengths' => !empty($strengths) ? 'نقاط القوة في: ' . implode(', ', array_unique($strengths)) . '.' : 'لا توجد نقاط قوة واضحة.',
            'weaknesses' => !empty($weaknesses) ? 'نقاط الضعف في: ' . implode(', ', array_unique($weaknesses)) . '.' : 'لا توجد نقاط ضعف رئيسية.',
            'opportunities' => !empty($opportunities) ? 'فرص التحسين في: ' . implode(', ', array_unique($opportunities)) . '.' : null,
            'threats' => !empty($threats) ? 'تهديدات: ' . implode(', ', array_unique($threats)) . '.' : null,
            'recommendations' => $recommendations,
        ];
    }

    private function computeSummary(float $percentage, int $criticalErrors): string
    {
        $text = 'تم تنفيذ الزيارة بنسبة ' . round($percentage, 2) . '%';
        $text .= $criticalErrors > 0
            ? " مع وجود {$criticalErrors} أخطاء حرجة."
            : ' دون أخطاء حرجة.';

        return $text;
    }
}
